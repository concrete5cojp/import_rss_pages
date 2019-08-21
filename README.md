# Import RSS Feed
A concrete5 add-on to import RSS feeds into concrete5 as pages

## Event

An example to customize how to import feeds

```
/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $director */
$director = $app->make(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class);
$director->addListener('on_after_page_created_from_feed', function ($event) {
    /** @var \Symfony\Component\EventDispatcher\GenericEvent $event */
    /** @var \Concrete\Core\Page\Page $page */
    $page = $event->getArgument('page');
    /** @var \Zend\Feed\Reader\Entry\EntryInterface $item */
    $item = $event->getArgument('item');

    /** @var \Concrete\Core\Tree\Type\Topic $root */
    $root = \Concrete\Core\Tree\Type\Topic::getByName('Channel');
    if (is_object($root)) {
        $author = $item->getAuthor();
        if ($author) {
            $topic = \Concrete\Core\Tree\Node\Type\Topic::getNodeByName($author['name']);
            if (!is_object($topic)) {
                $topic = \Concrete\Core\Tree\Node\Type\Topic::add($author['name'], $root->getRootTreeNodeObject());
            }
            $page->setAttribute('topic_channel', $topic);
        }
    }
});
```

## License

MIT
