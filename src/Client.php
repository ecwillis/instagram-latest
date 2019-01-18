<?php

namespace InstagramLatest;

use GuzzleHttp\Client as GClient;

class Client {
  protected $username;
  protected $gc;
  public function  __construct($un) {
    $this->username = $un;
    $this->gc = new GClient([
      'base_uri' => 'https://www.instagram.com',
      'timeout' => 30.0
    ]);

    $this->gce = new GClient([
      'base_uri' => 'https://api.instagram.com',
      'timeout' => 30.0
    ]);
  }

  public function host() {
    return "/{$this->username}";
  }

  protected function _extractIds($payload) {
    if (strlen($payload) < 100) {
      throw new \Error('Invalid Payload');
    }
    preg_match_all('/"shortcode":"([A-Za-z_\-0-9]+)"/', $payload, $out);

    return $out;
  }

  protected function _handleResponse($r) {
    if ($r->getStatusCode() !== 200) {
      // Log this...
      //throw new \Error('Resource Error');
      return [];
    }
    $body = $r->getBody();
    $payload = $body->getContents();
    return json_decode($payload);
  }

  protected function _getEmbeds($shortcodes) {
    $return = [];
    $gTest = new GClient();
    foreach($shortcodes as $code) {
      $resp = $this->gce->get('oembed', [
        'query' => [
          'omitscript' => 'true',
          'url' => "http://instagr.am/p/{$code}"
        ]
      ]);

      $js = $this->_handleResponse($resp);
      
      try {
        $testResp = $gTest->head($js->thumbnail_url);
        $return[] = $js;
      } catch( \Exception $e) {}
    }

    return $return;
  }

  public function getPosts() {
    $resp = $this->gc->get($this->username, [
      'headers' => [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
      ]
    ]);
    if ($resp->getStatusCode() !== 200) {
      throw new \Error('User not found');
    }

    $body = $resp->getBody();
    $payload = $body->getContents();

    $shortCodes = $this->_extractIds($payload);
  
    return $this->_getEmbeds($shortCodes[1]);
  }
}
