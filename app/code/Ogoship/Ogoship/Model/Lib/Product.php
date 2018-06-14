<?php

namespace Ogoship\Ogoship\Model\Lib;

use Ogoship\Ogoship\Model\Lib\RESTclient;

class NettivarastoAPI_Product extends \Ogoship\Ogoship\Model\Lib\Object
{
  protected $api = null;
  protected $createNew = true;
  protected $productCode = 0;
  
  public function __construct(\Ogoship\Ogoship\Model\Lib\API $api, $productCode)
  {
    $this->api = $api;
    $this->productCode = $productCode;
  }
  
  public function save()
  {
    $method = 'POST';
    $shaMethod = 'update';
    if ($this->createNew)
    {
      $method = 'PUT';
      $shaMethod = 'add';
    }
    $restClient = new NettivarastoAPI_RESTclient($this->api, $method, '/Product/' . urlencode($this->productCode),
                                                 array('product', $shaMethod, $this->productCode));
    $restClient->setPostData(array('Product' => $this->getArrayOfModifiedAttributes()));
    
    $resultArray = array();
    $success = $restClient->execute($resultArray);
    
    if ($success)
    {
      $this->attributesModified = array();
      
      if ($this->createNew)
      {
        $this->createNew = false;
      }
    }
    
    return $success;
  }
  
  public function delete()
  {
    $restClient = new NettivarastoAPI_RESTclient($this->api, 'DELETE', '/Product/' . urlencode($this->productCode),
                                                 array('product', 'remove', $this->productCode));
    $resultArray = array();
    return $restClient->execute($resultArray);
  }
  
  static function createFromArray(NettivarastoAPI $api, $data)
  {
    if (array_key_exists('Code', $data))
    {
      $product = new NettivarastoAPI_Product($api, $data['Code']);
      $product->createNew = false;
 
      foreach ($data as $key => $value)
      {
        if ($key != 'Code')
        {
          $product->attributes[$key] = $value;
        }
      }
      
      return $product;
    }
    else
    {
      return null;
    }
  }
  
  //
  // Getter and setter methods
  //
  
  /**
   * Get display name of product.
   */
  public function getName()
  {
    return $this->getAttribute('Name');
  }

  /**
   * Set display name of product.
   */
  public function setName($value)
  {
    $this->setAttribute('Name', $value);
  }
  
  /**
   * Get additional information about product.
   */
  public function getDescription()
  {
    return $this->getAttribute('Description');
  }

  /**
   * Set additional information about product.
   */
  public function setDescription($value)
  {
    $this->setAttribute('Description', $value);
  }
  
  /**
   * Get unique product code.
   */
  public function getCode()
  {
    return $this->productCode;
  }
  
  /**
   * Get manufacturer given code of this product.
   */
  public function getManufacturerCode()
  {
    return $this->getAttribute('ManufacturerCode');
  }

  /**
   * Set manufacturer given code of this product.
   */
  public function setManufacturerCode($value)
  {
    $this->setAttribute('ManufacturerCode', $value);
  }
  
  /**
   * Get EAN code of product.
   */
  public function getEANCode()
  {
    return $this->getAttribute('EANCode');
  }

  /**
   * Set EAN code of product.
   */
  public function setEANCode($value)
  {
    $this->setAttribute('EANCode', $value);
  }
  
  /// \todo Setter-functions for these?
  
  /**
   * Get width of product.
   */
  public function getWidth()
  {
    return $this->getAttribute('Width', -1);
  }

  /**
   * Get height of product.
   */
  public function getHeight()
  {
    return $this->getAttribute('Height', -1);
  }
  
  /**
   * Get depth of product.
   */
  public function getDepth()
  {
    return $this->getAttribute('Depth', -1);
  }

  /**
   * Get weight of product.
   */
  public function getWeight()
  {
    return $this->getAttribute('Weight', -1);
  }

  /**
   * Get alarm level. Merchant can receive reports if stock is below this alarm level.
   */
  public function getAlarmLevel()
  {
    return $this->getAttribute('AlarmLevel', -1);
  }
  
  /**
   * Set alarm level. Merchant can receive reports if stock is below this alarm level.
   */
  public function setAlarmLevel($value)
  {
    $this->setAttribute('AlarmLevel', $value);
  }

  /**
   * Get count of products available in stock.
   */
  public function getStock()
  {
    return $this->getAttribute('StockAvailable', 0);
  }
}

?>
