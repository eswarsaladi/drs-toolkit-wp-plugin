<?php

class Ceres_Drs_Fetcher extends Ceres_Abstract_Fetcher {
  
  protected $endpoint = "https://repository.library.northeastern.edu/api/v1";
  
  public function __construct(string $resourceId = '', array $queryOptions = array(), array $queryParams = array() ) {
    parent::__construct($resourceId, $queryOptions, $queryParams);
    if ($resourceId == '') {
      $this->resourceId = $this->getSettingsPid();
    }
  }
  
  public function buildQueryString() {
    
    $dak = constant("DPLA_API_KEY");
    $dau = constant("DRS_API_USER");
    $dap = constant("DRS_API_PASSWORD");
    
    $url = $this->endpoint;
    $url .= '/' . $this->queryOptions['action'];
    
    if (isset($this->queryOptions['sub_action'])) {
      
      //DRS subaction of content_objects has special needs for building the URL
      //PMJ assuming this only gets invoked when the action is 'files'
      switch ($this->queryOptions['sub_action']) {
        case 'content_objects':
          $url .= "{$this->resourceId}/$sub_action";
          break;
          
        case null:
          //do nothing since there's no subaction
          $url .= "{$this->resourceId}";
          break;
          
        default:
          //most common url construction
          $url .= "{$this->queryOptions['sub_action']}/{$this->resourceId}";
          break;
          
      }
    }
    
    if (! empty($this->queryParams)) {
      $url .= '?';
      foreach ($this->queryParams as $param=>$value) {
        $url .= "$param=$value&";
      }
    }

    
    if(!(empty($dau) || empty($dap))){
      $token = $this->drsAuth();
      if ($token != false && is_string($token))
        $url .= "token=$token";
    }
    
    return $url;
  }
  
  /**
   * The DRS part of the nee DRS Toolkit sets basically a default collection or set to draw from in the plugin's
   * settings, so this maintains using that setting if a $resourceId is omitted
   * 
   * @return string
   */
  
  public function getSettingsPid() {
    $collectionSetting = get_option('drstk_collection');
    $explodedCollectionSetting = explode("/", $collectionSetting);
    return end($explodedCollectionSetting);
  }
  
  /*DRS API Authenticate helper method*/
  public function drsAuth(){
    if(drstk_api_auth_enabled() == true){
      // Token is only good for one hour
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://repository.library.northeastern.edu/api/v1/auth_user");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "email=" . DRS_API_USER . "&password=" . DRS_API_PASSWORD);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $headers = array();
      $headers[] = "Content-Type: application/x-www-form-urlencoded";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $result = curl_exec($ch);
      
      // result should be json
      $data = json_decode($result, true);
      
      $token = $data["auth_token"];
      
      if (!empty($token)) {
        return $token;
      } else {
        return false;
      }
    }
    else {
      // No user and/or password set
      return false;
    }
  }
}
