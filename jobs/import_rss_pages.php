<?php
namespace Concrete\Package\ImportRssPages\Job;

use Concrete\Core\Attribute\Key\Factory;
use Concrete\Core\Config\Repository\Liaison;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\File\File as FileEntity;
use Concrete\Core\Entity\File\StorageLocation\StorageLocation;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Feed\FeedService;
use Concrete\Core\File\File;
use Concrete\Core\File\Service\Application;
use Concrete\Core\File\Service\Mime;
use Concrete\Core\File\StorageLocation\StorageLocationFactory;
use Concrete\Core\Http\Client\Client;
use Concrete\Core\Job\QueueableJob;
use Concrete\Core\Localization\Service\Date;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\Url\Url;
use Concrete\Core\Utility\Service\Text;
use Concrete\Core\Validation\SanitizeService;
use Concrete5cojp\ImportRssPages\Feed\Reader\Extension\Media\Entry;
use Concrete5cojp\ImportRssPages\Feed\Reader\StandaloneExtensionManager;
use Exception;
use InvalidArgumentException;
use League\Flysystem\AdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Zend\Feed\Reader\Entry\EntryInterface;
use Zend\Feed\Reader\Reader;
use ZendQueue\Message as ZendQueueMessage;
use ZendQueue\Queue as ZendQueue;

class ImportRssPages extends QueueableJob
{
    /**
     * @var int
     */
    public $jQueueBatchSize = 1;

    const EVENT_NAME_ON_BEFORE_LOAD = 'on_before_load_feed_to_import';
    const EVENT_NAME_ON_BEFORE_IMPORT = 'on_before_add_page_from_feed';
    const EVENT_NAME_ON_AFTER_IMPORT = 'on_after_add_page_from_feed';

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;
    /**
     * @var FeedService
     */
    protected $feedService;
    /**
     * @var Text
     */
    protected $textService;
    /**
     * @var Factory
     */
    protected $attributeKeyFactory;
    /** @var SanitizeService */
    protected $sanitizeService;
    /** @var \Concrete\Core\File\Service\File */
    protected $fileService;
    /**
     * @var Application
     */
    protected $fileApplication;
    /** @var Mime */
    protected $mimeService;
    /**
     * @var StorageLocation
     */
    protected $defaultStorageLocation;
    protected $eventDispatcher;
    /**
     * @var Liaison
     */
    protected $config;

    /**
     * ImportRssPages constructor.
     *
     * @param Factory $factory
     * @param StorageLocationFactory $storageLocationFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param PackageService $service
     */
    public function __construct(Factory $factory, StorageLocationFactory $storageLocationFactory, EventDispatcherInterface $eventDispatcher, PackageService $service)
    {
        $manager = new StandaloneExtensionManager();
        Reader::setExtensionManager($manager);
        Reader::registerExtension('Media');

        $this->app = Facade::getFacadeApplication();
        $this->feedService = $this->app->make('helper/feed');
        $this->textService = $this->app->make('helper/text');
        $this->attributeKeyFactory = $factory;
        $this->sanitizeService = $this->app->make('helper/security');
        $this->fileService = $this->app->make('helper/file');
        $this->fileApplication = $this->app->make('helper/concrete/file');
        $this->mimeService = $this->app->make('helper/mime');
        $this->defaultStorageLocation = $storageLocationFactory->fetchDefault();
        $this->eventDispatcher = $eventDispatcher;

        $pkg = $service->getClass('import_rss_pages');
        $this->config = $pkg->getFileConfig();
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return t('Import RSS Feed');
    }

    /**
     * @return string
     */
    public function getJobDescription()
    {
        return t('Import RSS Feeds into concrete5 as Pages');
    }

    /**
     * {@inheritdoc}
     */
    public function start(ZendQueue $q)
    {
        $feeds = (array) $this->config->get('feed.urls');
        foreach ($feeds as $url) {
            $q->send($url);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finish(ZendQueue $q)
    {
        return t('Import Finished.');
    }

    /**
     * {@inheritdoc}
     */
    public function processQueueItem(ZendQueueMessage $msg)
    {
        $typeID = $this->config->get('import.page_type');
        $type = Type::getByID($typeID);
        if (!is_object($type)) {
            throw new InvalidArgumentException(t('Invalid Page Type'));
        }
        $templateID = $this->config->get('import.page_template');
        $template = Template::getByID($templateID);
        if (!is_object($template)) {
            throw new InvalidArgumentException(t('Invalid Page Template'));
        }
        $pcID = $this->config->get('import.parent_page', Page::getHomePageID());
        $pc = Page::getByID($pcID);
        if (!is_object($pc) || $pc->isError()) {
            throw new InvalidArgumentException(t('Invalid Parent Page'));
        }
        $aLink = $this->attributeKeyFactory->getByID($this->config->get('import.attributes.link'));
        $aThumbnail = $this->attributeKeyFactory->getByID($this->config->get('import.attributes.thumbnail'));

        $url = $msg->body;

        if ($this->eventDispatcher->hasListeners(self::EVENT_NAME_ON_BEFORE_LOAD)) {
            $event = new GenericEvent();
            $event->setArgument('type', $type);
            $event->setArgument('template', $template);
            $event->setArgument('parent', $pc);
            $event->setArgument('akLink', $aLink);
            $event->setArgument('akThumbnail', $aThumbnail);
            $event->setArgument('feedURL', $url);
            $this->eventDispatcher->dispatch(self::EVENT_NAME_ON_BEFORE_LOAD, $event);
            $type = $event->getArgument('type');
            $template = $event->getArgument('template');
            $pc = $event->getArgument('parent');
            $aLink = $event->getArgument('akLink');
            $aThumbnail = $event->getArgument('akThumbnail');
            $url = $event->getArgument('feedURL');
            unset($event);
        }

        $feed = $this->feedService->load($url);
        /** @var EntryInterface|Entry $item */
        foreach ($feed as $item) {
            $handle = $this->textService->handle($item->getId());
            $title = $item->getTitle();
            $description = $item->getDescription();
            if (!$description) {
                $description = $item->getMediaDescription();
            }
            $thumbnail = $item->getMediaThumbnail();
            if ($this->eventDispatcher->hasListeners(self::EVENT_NAME_ON_BEFORE_IMPORT)) {
                $event = new GenericEvent();
                $event->setArgument('handle', $handle);
                $event->setArgument('title', $title);
                $event->setArgument('description', $description);
                $event->setArgument('thumbnail', $thumbnail);
                $this->eventDispatcher->dispatch(self::EVENT_NAME_ON_BEFORE_IMPORT, $event);
                $handle = $event->getArgument('handle');
                $title = $event->getArgument('title');
                $description = $event->getArgument('description');
                $thumbnail = $event->getArgument('thumbnail');
                unset($event);
            }
            
            $c = $this->getPageByHandle($handle);
            if (!is_object($c)) {
                $dateCreated = $item->getDateCreated();
                $link = $item->getLink();
                if (is_array($thumbnail) && isset($thumbnail['url']) && $thumbnail['url'] != '') {
                    $file = $this->getOrImportFile($thumbnail['url']);
                }

                $data = [
                    'cName' => $title,
                    'cHandle' => $handle,
                    'cDescription' => $description,
                    'cDatePublic' => $dateCreated->format(Date::DB_FORMAT),
                    'uID' => USER_SUPER_ID,
                ];
                $c = $pc->add($type, $data, $template);
                if (is_object($aLink)) {
                    $c->setAttribute($aLink, $link);
                }
                if (is_object($aThumbnail) && isset($file) && is_object($file)) {
                    $c->setAttribute($aThumbnail, $file);
                }

                if ($this->eventDispatcher->hasListeners(self::EVENT_NAME_ON_AFTER_IMPORT)) {
                    $event = new GenericEvent();
                    $event->setArgument('page', $c);
                    $event->setArgument('item', $item);
                    $this->eventDispatcher->dispatch(self::EVENT_NAME_ON_AFTER_IMPORT, $event);
                    unset($event);
                }
            }
        }
    }

    /**
     * @param $cHandle
     *
     * @return Page|null
     */
    protected function getPageByHandle($cHandle)
    {
        $siteTreeID = $this->app->make('site')->getSite()->getSiteTreeObject()->getSiteTreeID();

        /** @var Connection $db */
        $db = $this->app['database']->connection();
        $qb = $db->createQueryBuilder();
        $cID = $qb->select('p.cID')
            ->from('Pages', 'p')
            ->innerJoin('p', 'Collections', 'c', 'p.cID = c.cID')
            ->innerJoin('p', 'CollectionVersions', 'cv', 'p.cID = cv.cID')
            ->andWhere('p.cPointerID < 1')
            ->andWhere('p.cIsTemplate = 0')
            ->andWhere('cv.cvID = (select max(cvID) from CollectionVersions where cID = cv.cID)')
            ->andWhere('p.siteTreeID = :siteTreeID')
            ->andWhere('cv.cvHandle = :cvHandle')
            ->andWhere('p.cIsActive = :cIsActive')
            ->setParameter('cIsActive', true)
            ->setParameter('siteTreeID', $siteTreeID)
            ->setParameter('cvHandle', $cHandle)
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        $c = Page::getByID($cID);
        if (!is_object($c) || $c->isError()) {
            $c = null;
        }

        return $c;
    }

    /**
     * @param $url
     *
     * @return bool|FileEntity|null
     *
     * @throws UserMessageException
     */
    protected function getOrImportFile($url)
    {
        $filesystem = $this->defaultStorageLocation->getFileSystemObject();
        if ($filesystem) {
            $url = $this->sanitizeService->sanitizeURL($url);
            if ($url) {
                $urlObject = Url::createFromUrl($url);
                $fname = implode('_', $urlObject->getPath()->toArray());
                $sanitizedFilename = $this->fileService->sanitize($fname);
                $prefix = $this->generatePrefix();
                $resource = $this->readStream($url);
                if (is_resource($resource)) {
                    try {
                        $filesystem->writeStream(
                            $this->fileApplication->prefix($prefix, $sanitizedFilename),
                            $resource,
                            [
                                'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
                                'mimetype' => $this->mimeService->mimeFromExtension(
                                    $this->fileService->getExtension($sanitizedFilename)
                                ),
                            ]
                        );
                        $fv = File::add($sanitizedFilename, $prefix, ['fvTitle' => $fname, 'uID' => USER_SUPER_ID]);
                        if (is_object($fv)) {
                            $fv->refreshAttributes(true);

                            return $fv->getFile();
                        }
                    } catch (Exception $e) {
                        throw new UserMessageException($e->getMessage());
                    } finally {
                        @fclose($resource);
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function generatePrefix()
    {
        $prefix = mt_rand(10, 99) . time();

        return $prefix;
    }

    /**
     * @param $url
     *
     * @return bool|resource|null
     */
    protected function readStream($url)
    {
        /** @var Client $client */
        $client = $this->app->make('http/client');
        $request = $client->getRequest();
        $request->setUri($url);
        $response = $client->send();
        if ($response->isSuccess()) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $response->getBody());
            rewind($stream);

            return $stream;
        } else {
            return null;
        }
    }
}
