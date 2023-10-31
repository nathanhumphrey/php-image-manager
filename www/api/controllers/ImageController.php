<?php
// Create a php ImageController in the same style as the open UserController.php file.
// The ImageController should have the following methods:
//  - createImage($imagePath, $imageCaption, $imageType, $imageSize, $imageUploadDate, $userId)
//  - getImageByFilename($filename)
//  - getImagesForUser($userId)
//  - updateImage($image)
//  - deleteImage($filename)
//  - deleteImagesFromAlbum($albumName)
//  - deleteImagesForUser($userId)
//  - uploadImage($image, $userId, $albumName = null)

// Path: www/api/controllers/ImageController.php

require_once __DIR__ . '/../models/Image.php';

class ImageController {

    private $db;
    private $uploadPath = __DIR__ . '/../../images/';
    private static $instance = null;

    private function __construct($database) {
        $this->db = $database;
    }

    public static function getInstance($database) {
        if (is_null(self::$instance)) {
            self::$instance = new self($database);
        }

        return self::$instance;
    }

    /*
     * Returns an Image object or false if creation fails.
     * This method can only be called from within the class on successful upload.
     */
    private function createImage($imageCaption, $imageType, $imageSize, $imageUploadDate, $userId = null) {
        // Sanitize user input to prevent SQL injection
        $imageCaption = htmlspecialchars($imageCaption);
        $imageType = htmlspecialchars($imageType);
        $imageSize = htmlspecialchars($imageSize);
        $imageUploadDate = htmlspecialchars($imageUploadDate);
        $userId = htmlspecialchars($userId);

        // Create a new UUID id for the image
        $filename = uuid();

        // Insert new image into the database
        $insertImageQuery = "INSERT INTO images (filename, image_caption, image_type, image_size, image_upload_date, user_id) VALUES (:filename, :imageCaption, :imageType, :imageSize, :imageUploadDate, :userId)";
        $stmt = $this->db->prepare($insertImageQuery);
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':imageCaption', $imageCaption);
        $stmt->bindParam(':imageType', $imageType);
        $stmt->bindParam(':imageSize', $imageSize);
        $stmt->bindParam(':imageUploadDate', $imageUploadDate);
        $stmt->bindParam(':userId', $userId);

        if ($stmt->execute()) {
            // User registration successful
            return new Image($filename, $imageCaption, $imageType, $imageSize, $imageUploadDate, $userId);
        } else {
            // User registration failed
            return false;
        }
    }

    /*
     * Returns an Image object or false if the image is not found
     */
    public function getImageByFilename($filename) {
        // Sanitize user input to prevent SQL injection
        $filename = htmlspecialchars($filename);

        // Get image from the database
        $getImageQuery = "SELECT * FROM images WHERE filename = :filename";
        $stmt = $this->db->prepare($getImageQuery);
        $stmt->bindParam(':filename', $filename);
        $stmt->execute();
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Image found
            return new Image($image['filename'], $image['image_caption'], $image['image_type'], $image['image_size'], $image['image_upload_date'], $image['user_id']);
        } else {
            // Image not found
            return false;
        }
    }

    /*
     * Returns an array of Image objects or false if no images are found
     */
    public function getImagesForUser($userId) {
        // Sanitize user input to prevent SQL injection
        $userId = htmlspecialchars($userId);

        // Get images from the database
        $getImagesQuery = "SELECT * FROM images WHERE user_id = :userId";
        $stmt = $this->db->prepare($getImagesQuery);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($images) {
            // Images found
            $imageObjects = array();
            foreach ($images as $image) {
                $imageObjects[] = new Image($image['filename'], $image['image_caption'], $image['image_type'], $image['image_size'], $image['image_upload_date'], $image['user_id']);
            }
            return $imageObjects;
        } else {
            // Images not found
            return false;
        }
    }

    /*
     * Returns an Image object or false if the image is not found
     */
    public function updateImage($image) {
        // Sanitize user input to prevent SQL injection
        $filename = htmlspecialchars($image->filename);
        $imageCaption = htmlspecialchars($image->imageCaption);
        $imageType = htmlspecialchars($image->imageType);
        $imageSize = htmlspecialchars($image->imageSize);
        $imageUploadDate = htmlspecialchars($image->imageUploadDate);
        $userId = htmlspecialchars($image->userId);

        // Update image in the database
        $updateImageQuery = "UPDATE images SET image_caption = :imageCaption, image_type = :imageType, image_size = :imageSize, image_upload_date = :imageUploadDate, user_id = :userId WHERE filename = :filename";
        $stmt = $this->db->prepare($updateImageQuery);
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':imageCaption', $imageCaption);
        $stmt->bindParam(':imageType', $imageType);
        $stmt->bindParam(':imageSize', $imageSize);
        $stmt->bindParam(':imageUploadDate', $imageUploadDate);
        $stmt->bindParam(':userId', $userId);

        if ($stmt->execute()) {
            // Image update successful
            return new Image($filename, $imageCaption, $imageType, $imageSize, $imageUploadDate, $userId);
        } else {
            // Image update failed
            return false;
        }
    }

    /*
     * Returns true if the image is deleted or false if the image is not found.
     * Deletes the file from the server and the database.
     */
    public function deleteImage($filename) {
        // Sanitize user input to prevent SQL injection
        $filename = htmlspecialchars($filename);

        // Get image from the database
        $getImageQuery = "SELECT * FROM images WHERE filename = :filename";
        $stmt = $this->db->prepare($getImageQuery);
        $stmt->bindParam(':filename', $filename);
        $stmt->execute();
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Image found, delete the file from the server
            $imagePath = $this->uploadPath . $image['filename'];
            if (file_exists($imagePath)) {
                if (unlink($imagePath)) {
                    // Delete image from the database
                    $deleteImageQuery = "DELETE FROM images WHERE filename = :filename";
                    $stmt = $this->db->prepare($deleteImageQuery);
                    $stmt->bindParam(':filename', $filename);
                    $stmt->execute();

                    // Image delete successful
                    return true;
                } else {
                    return false;
                }
            } else {
                // File doesn't exist, delete image from the database
                $deleteImageQuery = "DELETE FROM images WHERE filename = :filename";
                $stmt = $this->db->prepare($deleteImageQuery);
                $stmt->bindParam(':filename', $filename);
                $stmt->execute();

                // Image delete successful
                return true;
            }
        } else {
            // Image not found
            return false;
        }
    }

    /*
     * Returns true if the images are deleted or false if no images are found.
     * Deletes the files from the server and the database.
     */
    public function deleteImagesFromAlbum($albumName, $fromStorage = false) {
        // Sanitize user input to prevent SQL injection
        $albumName = htmlspecialchars($albumName);
        $fromStorage = !!$fromStorage;

        if (!$fromStorage) {
            // Delete images from the database album_images table
            $deleteImagesQuery = "DELETE FROM album_images WHERE album_name = :albumName";
            $stmt = $this->db->prepare($deleteImagesQuery);
            $stmt->bindParam(':albumName', $albumName);
            $stmt->execute();

            // Images deleted from album successful
            return true;
        } else {
            // Get image filenames from the database
            $getImageFilenamesQuery = "SELECT filename FROM album_images WHERE album_name = :albumName";
            $stmt = $this->db->prepare($getImageFilenamesQuery);
            $stmt->bindParam(':albumName', $albumName);
            $stmt->execute();
            $imageFilenames = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($imageFilenames) {
                // Image filenames found, delete the files from the server
                foreach ($imageFilenames as $imageFilename) {
                    $imagePath = $this->uploadPath . $imageFilename['filename'];

                    if (file_exists($imagePath)) {
                        if (unlink($imagePath)) {
                            // Delete images from any and all albums in the database
                            $deleteImagesQuery = "DELETE FROM album_images WHERE filename = :filename";
                            $stmt = $this->db->prepare($deleteImagesQuery);
                            $stmt->bindParam(':filename', $imageFilename['filename']);
                            $stmt->execute();
                        } else {
                            return false;
                        }
                    } else {
                        // File doesn't exist
                        // Delete images from any and all albums in the database
                        $deleteImagesQuery = "DELETE FROM album_images WHERE filename = :filename";
                        $stmt = $this->db->prepare($deleteImagesQuery);
                        $stmt->bindParam(':filename', $imageFilename['filename']);
                        $stmt->execute();
                    }

                    // Delete image from the database
                    $deleteImagesQuery = "DELETE FROM images WHERE filename = :filename";
                    $stmt = $this->db->prepare($deleteImagesQuery);
                    $stmt->bindParam(':filename', $imageFilename['filename']);
                    $stmt->execute();

                    // Deleted all images
                    return true;
                }
            } else {
                // No images found
                return false;
            }
        }
    }

    /*
     * Returns true if the images are deleted or false if no images are found.
     * Deletes the files from the server and the database.
     */
    public function deleteImagesForUser($userId) {
        $encounteredError = false;

        // Sanitize user input to prevent SQL injection
        $userId = htmlspecialchars($userId);

        // Get images from the database
        $getImagesQuery = "SELECT * FROM images WHERE user_id = :userId";
        $stmt = $this->db->prepare($getImagesQuery);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($images) {
            // Images found, delete the files from the server
            foreach ($images as $image) {
                $imagePath = $this->uploadPath . $image['filename'];
                if (file_exists($imagePath)) {
                    if (unlink($imagePath)) {
                        // Delete image from any and all albums in the database
                        $deleteImagesQuery = "DELETE FROM album_images WHERE album_images.filename = :filename";
                        $stmt = $this->db->prepare($deleteImagesQuery);
                        $stmt->bindParam(':filename', $image['filename']);
                        $stmt->execute();
                    } else {
                        $encounteredError = true;
                    }
                } else {
                    // File doesn't exist, delete image from any and all albums in the database
                    $deleteImagesQuery = "DELETE FROM album_images WHERE album_images.filename = :filename";
                    $stmt = $this->db->prepare($deleteImagesQuery);
                    $stmt->bindParam(':filename', $image['filename']);
                    $stmt->execute();
                }
            }

            if (!$encounteredError) {
                // Delete images from the database
                $deleteImagesQuery = "DELETE FROM images WHERE user_id = :userId";
                $stmt = $this->db->prepare($deleteImagesQuery);
                $stmt->bindParam(':userId', $userId);
                $stmt->execute();

                // Images delete successful
                return true;
            }
            return false;
        } else {
            // Images not found
            return false;
        }
    }

    // Uploads an image file to the server and returns the path with new filename
    public function uploadImage($image, $userId, $albumName = null) {
        // Sanitize user input to prevent SQL injection
        $userId = htmlspecialchars($userId);
        $albumName = htmlspecialchars($albumName);

        // Check if the image is valid
        if ($image['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Check if the image is a valid image type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $validExts = array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        );
        $ext = array_search($finfo->file($image['tmp_name']), $validExts, true);
        if (false === $ext) {
            return false;
        }

        // Check if the image is a valid size
        if ($image['size'] > 1000000) {
            return false;
        }

        // Generate a unique filename
        $filename = sha1_file($image['tmp_name']);
        $destination = sprintf('%s/%s.%s', $this->uploadPath, $filename, $ext);

        // Move the file to the destination
        if (!move_uploaded_file($image['tmp_name'], $destination)) {
            return false;
        }

        // Create a new image in the database
        $image = $this->createImage($destination, '', $ext, $image['size'], date('Y-m-d H:i:s'), $userId, $albumName);

        if ($image != false) {
            // Image upload successful
            return $image;
        } else {
            // Image upload failed
            return false;
        }
    }
}
