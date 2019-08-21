<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Form\Service\Widget\PageSelector;

/** @var PageSelector $pageSelector */
$feeds = (isset($feeds)) ? (string) $feeds : '';
$types = (isset($types)) ? (array) $types : [];
$type = (isset($type)) ? (string) $type : '';
$templates = (isset($templates)) ? (array) $templates : [];
$template = (isset($template)) ? (string) $template : '';
$pc = (isset($pc)) ? (int) $pc : null;
$attributes = (isset($attributes)) ? (array) $attributes : [];
$aLink = (isset($aLink)) ? (string) $aLink : '';
$aThumbnail = (isset($aThumbnail)) ? (string) $aThumbnail : '';

?>
<form role="form" method="post" action="<?php echo $controller->action('save_settings'); ?>">
    <?php $token->output('save_settings'); ?>
    <fieldset>
        <legend><?= t('RSS Feeds to import'); ?></legend>
        <div class="form-group">
            <?= $form->label('feeds', t('Feed URL')); ?>
            <?= $form->textarea('feeds', $feeds, ['rows' => 5]); ?>
            <span class="help-block"><?= t('You can set multiple URLs separated by a new line.'); ?></span>
        </div>
    </fieldset>
    <fieldset>
        <legend><?= t('Configurations for Imported Pages'); ?></legend>
        <div class="form-group">
            <?= $form->label('type', t('Page Type')); ?>
            <?= $form->select('type', $types, $type); ?>
        </div>
        <div class="form-group">
            <?= $form->label('template', t('Page Template')); ?>
            <?= $form->select('template', $templates, $template); ?>
        </div>
        <div class="form-group">
            <?= $form->label('pc', t('Parent Page')); ?>
            <?= $pageSelector->selectPage('pc', $pc); ?>
        </div>
    </fieldset>
    <fieldset>
        <legend><?= t('Attribute Mapping'); ?></legend>
        <div class="form-group">
            <?= $form->label('aLink', t('Original Link')); ?>
            <?= $form->select('aLink', $attributes, $aLink); ?>
        </div>
        <div class="form-group">
            <?= $form->label('aThumbnail', t('Thumbnail')); ?>
            <?= $form->select('aThumbnail', $attributes, $aThumbnail); ?>
        </div>
    </fieldset>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button type="submit" class="btn btn-primary pull-right">
                <?php echo t('Save'); ?>
            </button>
        </div>
    </div>
</form>
