<?php
namespace Concrete5cojp\ImportRssPages\Feed\Reader;

use Concrete5cojp\ImportRssPages\Feed\Reader\Extension\Media\Entry;
use Zend\Feed\Reader\ExtensionManagerInterface;
use Zend\Feed\Reader\Extension;

class StandaloneExtensionManager implements ExtensionManagerInterface
{
    private $extensions = [
        'Atom\Entry' => 'Zend\Feed\Reader\Extension\Atom\Entry',
        'Atom\Feed' => 'Zend\Feed\Reader\Extension\Atom\Feed',
        'Content\Entry' => 'Zend\Feed\Reader\Extension\Content\Entry',
        'CreativeCommons\Entry' => 'Zend\Feed\Reader\Extension\CreativeCommons\Entry',
        'CreativeCommons\Feed' => 'Zend\Feed\Reader\Extension\CreativeCommons\Feed',
        'DublinCore\Entry' => 'Zend\Feed\Reader\Extension\DublinCore\Entry',
        'DublinCore\Feed' => 'Zend\Feed\Reader\Extension\DublinCore\Feed',
        'Podcast\Entry' => 'Zend\Feed\Reader\Extension\Podcast\Entry',
        'Podcast\Feed' => 'Zend\Feed\Reader\Extension\Podcast\Feed',
        'Slash\Entry' => 'Zend\Feed\Reader\Extension\Slash\Entry',
        'Syndication\Feed' => 'Zend\Feed\Reader\Extension\Syndication\Feed',
        'Thread\Entry' => 'Zend\Feed\Reader\Extension\Thread\Entry',
        'WellFormedWeb\Entry' => 'Zend\Feed\Reader\Extension\WellFormedWeb\Entry',
        'Media\Entry' => Entry::class,
    ];

    /**
     * Do we have the extension?
     *
     * @param  string $extension
     *
     * @return bool
     */
    public function has($extension)
    {
        return array_key_exists($extension, $this->extensions);
    }

    /**
     * Retrieve the extension.
     *
     * @param  string $extension
     *
     * @return Extension\AbstractEntry|Extension\AbstractFeed
     */
    public function get($extension)
    {
        $class = $this->extensions[$extension];

        return new $class();
    }
}
