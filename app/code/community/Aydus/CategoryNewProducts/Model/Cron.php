<?php

/**
 * CategoryNewProducts cron
 *
 * @category   Aydus
 * @package    Aydus_CategoryNewProducts
 * @author     Aydus <davidt@aydus.com>
 */

class Aydus_CategoryNewProducts_Model_Cron
{
    protected $_newProductsCollections = array();
    
    /**
     * 
     * @param Mage_Cron_Model_Schedule $schedule
     * @return Aydus_CategoryNewProducts_Model_Cron
     */
    public function categoryNewProducts($schedule)
    {
        $newCategories = Mage::getModel('catalog/category')->getCollection();
        
        $newCategories->addAttributeToFilter('new_products', 1);
        
        if ($newCategories->getSize()>0){

            foreach ($newCategories as $newCategory){
                
                $newCategoryProductCollection = $newCategory->getProductCollection();
                
                if ($newCategoryProductCollection->getSize() > 0){
                    
                    foreach ($newCategoryProductCollection as $newCategoryProduct){
                        
                        try {
                            
                            $categoryIds = $newCategoryProduct->getCategoryIds();
                            $index = array_search($newCategoryProduct->getId(), $categoryIds);
                            
                            if (is_numeric($index)){
                                unset($categoryIds[$index]);
                                $newCategoryProduct->setCategoryIds($categoryIds);
                                $newCategoryProduct->save();
                            }   
                                                     
                        } catch(Exception $e){
                            
                            Mage::log($e->getMessage,null, 'aydus_categorynewproducts.log');
                        }
                        
                    }
                    
                }
                
                $storeId = $newCategory->getStoreId();
                
                $newProductsCollection = $this->_getNewProductsCollection($storeId);
                
                if ($newProductsCollection->getSize()>0){
                
                    foreach ($newProductsCollection as $newProduct){
                
                        try {
                            
                            $categoryIds = $newProduct->getCategoryIds();
                            $categoryIds[] = $newCategory->getId();
                            
                            $newProduct->setCategoryIds($categoryIds);
                            $newProduct->save();
                            
                        } catch(Exception $e){
                            
                            Mage::log($e->getMessage,null, 'aydus_categorynewproducts.log');
                        }
                        
                    }
                
                }            
                    
            }
            
        }
        
        $message = 'Category New Products cron completed. Number of categories: '. 
            (int)$newCategories->getSize(). ', number of products: '.$newProductsCollection->getSize();
        
        Mage::log($message,null, 'aydus_categorynewproducts.log');
        
        return $message;
    }
    
    protected function _getNewProductsCollection($storeId)
    {
        if (!isset($this->_newProductsCollections[$storeId])){
            
            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->setStoreId($storeId);
            
            //Mage_Catalog_Block_Product_New
            $todayStartOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('00:00:00')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
            
            $todayEndOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('23:59:59')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
            
            $collection
            ->addAttributeToFilter('news_from_date', array('or'=> array(
                    0 => array('date' => true, 'to' => $todayEndOfDayDate),
                    1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter('news_to_date', array('or'=> array(
                    0 => array('date' => true, 'from' => $todayStartOfDayDate),
                    1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter(
                    array(
                            array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
                            array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
                    )
            )
            ->addAttributeToSort('news_from_date', 'desc');
            
            $this->_newProductsCollections[$storeId] = $collection;
        }
        
        return @$this->_newProductsCollections[$storeId];
        
    }
    
}