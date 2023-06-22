<?php

namespace App\Services;

class QueryService
{
  public function search(string $key, string $query, int $platform)
  {
    $url = "https://api.rawg.io/api/games?search=" . $query
      . "&key=" . $key
      . "&platforms=" . $platform;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $results['success'] = curl_exec($curl);
    $results['failure'] = curl_error($curl);
    curl_close($curl);

    return $results;
  }
}
