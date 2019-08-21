<?php
namespace Concrete5cojp\ImportRssPages\Feed\Reader\Extension\Media;

use Zend\Feed\Reader\Extension\AbstractEntry;

class Entry extends AbstractEntry
{
    /**
     * @return array
     */
    public function getMediaContent()
    {
        if (isset($this->data['mediacontent'])) {
            return $this->data['mediacontent'];
        }

        $content = [];
        $url = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@url)');
        if ($url) {
            $content['url'] = $url;
        }
        $fileSize = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@fileSize)');
        if ($fileSize) {
            $content['fileSize'] = $fileSize;
        }
        $type = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@type)');
        if ($type) {
            $content['type'] = $type;
        }
        $medium = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@medium)');
        if ($medium) {
            $content['medium'] = $medium;
        }
        $isDefault = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@isDefault)');
        if ($isDefault) {
            $content['isDefault'] = $isDefault;
        }
        $expression = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@expression)');
        if ($expression) {
            $content['expression'] = $expression;
        }
        $bitrate = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@bitrate)');
        if ($bitrate) {
            $content['bitrate'] = $bitrate;
        }
        $framerate = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@framerate)');
        if ($framerate) {
            $content['framerate'] = $framerate;
        }
        $samplingrate = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@samplingrate)');
        if ($samplingrate) {
            $content['samplingrate'] = $samplingrate;
        }
        $channels = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@channels)');
        if ($channels) {
            $content['channels'] = $channels;
        }
        $duration = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@duration)');
        if ($duration) {
            $content['duration'] = $duration;
        }
        $height = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@height)');
        if ($height) {
            $content['height'] = $height;
        }
        $width = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@width)');
        if ($width) {
            $content['width'] = $width;
        }
        $lang = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:content/@lang)');
        if ($lang) {
            $content['lang'] = $lang;
        }

        if (!$content) {
            $content = null;
        }

        $this->data['mediacontent'] = $content;

        return $this->data['mediacontent'];
    }

    /**
     * @return string
     */
    public function getMediaRating()
    {
        if (isset($this->data['mediarating'])) {
            return $this->data['mediarating'];
        }

        $rating = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:rating)');

        if (!$rating) {
            $rating = null;
        }

        $this->data['mediarating'] = $rating;

        return $this->data['mediarating'];
    }

    /**
     * @return string
     */
    public function getMediaTitle()
    {
        if (isset($this->data['mediatitle'])) {
            return $this->data['mediatitle'];
        }

        $title = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:title)');

        if (!$title) {
            $title = null;
        }

        $this->data['mediatitle'] = $title;

        return $this->data['mediatitle'];
    }

    /**
     * @return string
     */
    public function getMediaDescription()
    {
        if (isset($this->data['mediadescription'])) {
            return $this->data['mediadescription'];
        }

        $description = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:description)');

        if (!$description) {
            $description = null;
        }

        $this->data['mediadescription'] = $description;

        return $this->data['mediadescription'];
    }

    /**
     * @return string
     */
    public function getMediaKeywords()
    {
        if (isset($this->data['mediakeywords'])) {
            return $this->data['mediakeywords'];
        }

        $keywords = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:keywords)');

        if (!$keywords) {
            $keywords = null;
        }

        $this->data['mediakeywords'] = $keywords;

        return $this->data['mediakeywords'];
    }

    /**
     * @return array
     */
    public function getMediaThumbnail()
    {
        if (isset($this->data['mediathumbnail'])) {
            return $this->data['mediathumbnail'];
        }

        $thumbnail = [];
        $url = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:thumbnail/@url)');
        if ($url) {
            $thumbnail['url'] = $url;
        }
        $width = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:thumbnail/@width)');
        if ($width) {
            $thumbnail['width'] = $width;
        }
        $height = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/media:group/media:thumbnail/@height)');
        if ($height) {
            $thumbnail['height'] = $height;
        }

        if (!$thumbnail) {
            $thumbnail = null;
        }

        $this->data['mediathumbnail'] = $thumbnail;

        return $this->data['mediathumbnail'];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNamespaces()
    {
        $this->xpath->registerNamespace('media', 'http://search.yahoo.com/mrss/');
    }
}
