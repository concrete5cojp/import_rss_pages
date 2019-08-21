<?php
namespace Concrete\Package\ImportRssPages;

use Concrete\Core\Package\Package;

class Controller extends Package
{
    protected $appVersionRequired = '8.5.1';
    protected $pkgHandle = 'import_rss_pages';
    protected $pkgVersion = '0.0.1';
    protected $pkgAutoloaderRegistries = [
        'src' => '\Concrete5cojp\ImportRssPages',
    ];

    /**
     * {@inheritdoc}
     */
    public function getPackageName()
    {
        return t('Import RSS Feed');
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageDescription()
    {
        return t('Import RSS Feeds into concrete5 as Pages');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installContentFile('config/jobs.xml');
        $this->installContentFile('config/singlepages.xml');

        return $pkg;
    }
}
