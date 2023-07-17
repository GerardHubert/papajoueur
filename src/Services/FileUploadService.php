<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

class FileUploadService
{
  /**
   * traiter l'upload d'image à l'inscription ici
   * la fonction retourne true en cas de succes
   * retourne un tableau avec les erreurs en cas d'erreur
   */
  public static function avatarUpload(array $file): bool | array
  {
    $error = [];
    // définition du répertoire de destination de l'image
    $targetDirectory = "uploads/avatar/";
    // définition de la taille autorisée
    $maxSize = 2000000;
    // définition des extensions autorisées
    $extensions = ['jpg', 'jpeg', 'png', 'bmp'];
    // définition des types autorisés
    $types = ['image/jpg', 'image/jpeg', 'image/png', 'image/bmp'];

    // vérification que l'image a bien été uploadée
    if (!is_uploaded_file($file['tmp_name'])) {
      // vérification de la taille
      if ($file['error'] === 1) {
        throw new Exception("Le fichier est trop volumineux (taille max 2Mo)");
        return false;
      }
      throw new Exception("un problème est survenu lors de l'upload.");
      return false;
    }

    // vérification de double extensions
    $testFileName = explode('.', strtolower($file['name']));
    if (count($testFileName) !== 2) {
      throw new Exception("Danger : risque de double extension !");
      return false;
    }

    // vérification que l'extension est autorisée
    if (!in_array($testFileName[1], $extensions)) {
      throw new Exception("Cette extension n'est pas autorisée.");
      return false;
    }

    // vérification du type
    if (!in_array($file['type'], $types)) {
      throw new Exception("Le type n'est pas autorisé");
      return false;
    }

    // si tous les tests sont passés, on attribue un nom unique à l'image
    $targetName = uniqid('avatar_') . '.' . $testFileName[1];
    $fullName = $targetDirectory . $targetName;

    // puis on peut l'uploader
    if (move_uploaded_file($file['tmp_name'], $fullName) === false) {
      throw new Exception("Le fichier n'a pas pu être uploadé, merci de réessayer");
      return false;
    } else {
      return [true, $fullName];
    };

    // on return true + le nom du fichier pour que le controller puisse l'associer au nouvel utilisateur
  }
}
