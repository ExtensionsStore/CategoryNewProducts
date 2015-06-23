<?php
/**
 * Aydus_CategoryNewProducts installer
 *
 * @category   Aydus
 * @package    Aydus_CategoryNewProducts
 * @author     Aydus <davidt@aydus.com>
 */

$installer = $this;
$installer->startSetup();

$attributeCode = 'new_products';
$entityTypeId     = $installer->getEntityTypeId('catalog_category');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroups  = Mage::getModel('eav/entity_attribute_group')->getCollection();
$attributeGroups->addFieldToFilter('attribute_set_id', $attributeSetId);
$attributeGroups->addFieldToFilter('attribute_group_name', 'Display Settings');

$attributeGroupId = $attributeGroups->getFirstItem()->getId(); 

$attributeId = $installer->getAttributeId($entityTypeId, $attributeCode);

if (!$attributeId){

    $installer->addAttribute('catalog_category', $attributeCode,  array(
            'type'     => 'int',
            'label'    => 'Show New Products',
            'input'    => 'select',
            'source'   => 'eav/entity_attribute_source_boolean',
            'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'required' => false,
            'default'  => 0,
            'user_defined' => 1,
    ));

    $installer->addAttributeToGroup(
            $entityTypeId,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            '11'
    );

    $attributeId = $installer->getAttributeId($entityTypeId, $attributeCode);

    $installer->run("
            INSERT INTO `{$installer->getTable('catalog_category_entity_int')}`
            (`entity_type_id`, `attribute_id`, `entity_id`, `value`)
            SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
            FROM `{$installer->getTable('catalog_category_entity')}`;
            ");
}

$installer->endSetup();
