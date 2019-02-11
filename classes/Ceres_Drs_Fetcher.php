<?php

class Ceres_Drs_Fetcher extends Ceres_Abstract_Fetcher {

  protected $endpoint = "https://repository.library.northeastern.edu/api/v1";

  public function __construct(array $queryOptions = array(), array $queryParams = array(), $resourceId = null) {
    parent::__construct($queryOptions, $queryParams, $resourceId);

    if (is_null($resourceId)) {
      $this->resourceId = $this->getPidFromSettings();
    } else {
      $this->resourceId = $resourceId;
    }
  }

  public function buildQueryString() {
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
          $url .= "/{$this->queryOptions['sub_action']}/{$this->resourceId}";
          break;
      }
    } else {
      $url .= '/' . $this->resourceId;
    }
    
    if (! empty($this->queryParams)) {
      $url .= '?';
      foreach ($this->queryParams as $param=>$value) {
        if ($param == 'q') {
          $value = sanitize_text_field($value);
        }
        $url .= "$param=$value&";
      }
    }
    
    $dau = constant("DRS_API_USER");
    $dap = constant("DRS_API_PASSWORD");
    
    if(!(empty($dau) || empty($dap))) {

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

  public function getPidFromSettings() {
    $collectionSetting = get_option('drstk_collection');
    $explodedCollectionSetting = explode("/", $collectionSetting);
    return end($explodedCollectionSetting);
  }

  public function parseItemsData() {
    $this->itemsData = $this->responseData['output']['response']['response']['docs'];
  }

  public function hasNextPage() {
    $paginationData = $this->responseData['output']['pagination']['table'];
    $currentPage = $paginationData['current_page'];
    $lastPage = $paginationData['num_pages'];
    if ($currentPage < $lastPage) {
      return true;
    }
    return false;
  }

  public function fetchNextPage() {
    if ($this->hasNextPage()) {
      $paginationData = $this->responseData['output']['pagination']['table'];
      $currentPage = $paginationData['current_page'];
      $nextStartPage = $currentPage + 1;
      $this->setQueryParam('page', $nextStartPage);
      $this->fetchData();
    }
  }

  /*DRS API Authenticate helper method*/
  // @TODO consider if this can be rolled into the rest of the fetching system?
  // comes from a site-specific customization in DRSTK
  public function drsAuth() {
    
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

  public function getItemDataById($itemId) {
    //there's discussion about indexing the response by id, so hopefully
    //someday this looping won't be needed 
    foreach($this->itemsData as $itemData) {
      if($itemData['id'] == $itemId) {
        return $itemData;
      }
    }
  }

  public function parseJwPlayerData($itemId) {
    $itemData = $this->getItemDataById($itemId);
    $mediaPid = $this->parseContentObjectId($itemId);
    
    $imageUrl = 'https://repository.library.northeastern.edu' . $itemData['fields_thumbnail_list_tesim'][3];
    $plainMediaUrl = "https://repository.library.northeastern.edu/wowza/$mediaPid/plain";
    $playlistMediaUrl = "https://repository.library.northeastern.edu/wowza/$mediaPid/playlist.m3u8";
    
    switch ($itemData['canonical_class_tesim'][0]) {
      case 'AudioFile':
        $type = 'mp3';
        break;
        
      case 'VideoFile':
        $type = 'mp4';
        break;
    }
    return array($plainMediaUrl, $playlistMediaUrl, $type, $imageUrl);
  }

  public function parseContentObjectId($itemId) {
    //it's somewhat amiss to not use the methods in this class to build the string
    //but if I'm looping through a search result I don't want to reset the params
    //and break pagination
    
    $queryString = $this->endpoint . "/files/$itemId/content_objects";
    $contentObjectsData = $this->fetchData($queryString, true);

    //PHP 7 has array_first_key() to avoid this reset/key stuff, but I can't assume PHP7
    reset($contentObjectsData['output']['canonical_object']);
    $canonicalObjectUrl = key($contentObjectsData['output']['canonical_object']);
    $canonicalObjectUrlParts = explode('/', $canonicalObjectUrl);
    
    $parsedCanonicalObjectUrl = parse_url($canonicalObjectUrl);
    $explodedPath = explode('/', $parsedCanonicalObjectUrl['path']);
    
    //explodedPath looks like /downloads/{pid}
    $canonicalObjectId = $explodedPath[2];
    return $canonicalObjectId;
  }
}
