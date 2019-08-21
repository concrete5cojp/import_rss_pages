<?php
namespace Concrete\Package\ImportRssPages\Controller\SinglePage\Dashboard\Pages;

use Concrete\Core\Attribute\AttributeKeyInterface;
use Concrete\Core\Attribute\Key\Key;
use Concrete\Core\Config\Repository\Liaison;
use Concrete\Core\Http\Request;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

class ImportRssPages extends DashboardPageController
{
    public function view()
    {
        $config = $this->getConfig();

        $feeds = (array) $config->get('feed.urls');
        $feeds = implode(PHP_EOL, $feeds);
        $typeID = $config->get('import.page_type');
        $templateID = $config->get('import.page_template');
        $pc = $config->get('import.parent_page');
        $aLink = $config->get('import.attributes.link');
        $aThumbnail = $config->get('import.attributes.thumbnail');

        $this->set('feeds', $feeds);
        $this->set('type', $typeID);
        $this->set('template', $templateID);
        $this->set('pc', $pc);
        $this->set('aLink', $aLink);
        $this->set('aThumbnail', $aThumbnail);

        $types = [
            '' => t('** Select Page Type'),
        ];
        /** @var Type $type */
        foreach (Type::getList() as $type) {
            $types[$type->getPageTypeID()] = $type->getPageTypeDisplayName();
        }
        $this->set('types', $types);

        $templates = [
            '' => t('** Select Page Template'),
        ];
        /** @var \Concrete\Core\Entity\Page\Template $template */
        foreach (Template::getList() as $template) {
            $templates[$template->getPageTemplateID()] = $template->getPageTemplateDisplayName();
        }
        $this->set('templates', $templates);

        $pageSelector = $this->app->make('helper/form/page_selector');
        $this->set('pageSelector', $pageSelector);

        $attributes = [
            '' => t('** Select Attribute'),
        ];
        /** @var AttributeKeyInterface $ak */
        foreach (Key::getList('collection') as $ak) {
            $attributes[$ak->getAttributeKeyID()] = $ak->getController()->getAttributeKey()->getAttributeKeyDisplayName();
        }
        $this->set('attributes', $attributes);
    }

    /**
     * @return Liaison
     */
    private function getConfig()
    {
        /** @var PackageService $service */
        $service = $this->app->make(PackageService::class);
        $pkg = $service->getClass('import_rss_pages');

        return $pkg->getFileConfig();
    }

    public function updated()
    {
        $this->set('message', t('Settings saved.'));
        $this->view();
    }

    public function save_settings()
    {
        if ($this->token->validate('save_settings')) {
            if (Request::isPost()) {
                $feeds = preg_split('/\r\n|\r|\n/', $this->post('feeds'));
                $type = $this->post('type');
                $template = $this->post('template');
                $pc = $this->post('pc');
                $aLink = $this->post('aLink');
                $aThumbnail = $this->post('aThumbnail');
                $config = $this->getConfig();
                $config->save('feed.urls', $feeds);
                $config->save('import.page_type', $type);
                $config->save('import.page_template', $template);
                $config->save('import.parent_page', $pc);
                $config->save('import.attributes.link', $aLink);
                $config->save('import.attributes.thumbnail', $aThumbnail);
                /** @var ResolverManagerInterface $resolver */
                $resolver = $this->app->make(ResolverManagerInterface::class);

                return new RedirectResponse((string) $resolver->resolve(['/dashboard/pages/import_rss_pages', 'updated']));
            }
        } else {
            $this->set('error', [$this->token->getErrorMessage()]);
        }
    }
}
