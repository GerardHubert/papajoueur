<?php

namespace App\Services;

class QueryService
{
  public function search(string $key, string $query, int $platform): array
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

  public function findById(int $id, string $key)
  {
    $url = "https://api.rawg.io/api/games/" . $id . "?key=" . $key;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $result['game'] = curl_exec($curl);
    $result['failure'] = curl_error($curl);
    curl_close($curl);

    return $result;
  }
}
