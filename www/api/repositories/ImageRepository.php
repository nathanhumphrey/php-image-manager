<?php

require_once __DIR__ . '/../models/Album.php';
require_once __DIR__ . '/../models/Image.php';

class ImageRepository {

    private $db;
    // Fallback upload path
    private $uploadPath = __DIR__ . '/../../images/';
    private static $instance = null;

    private function __construct($database, $uploadPath) {
        $this->db = $database;
        if (!is_null($uploadPath)) {
            $this->uploadPath = $uploadPath;
        }
    }

    public static function getInstance($database, $uploadPath = null) {
        if (is_null(self::$instance)) {
            self::$instance = new self($database, $uploadPath);
        }

        return self::$instance;
    }

    /*
     * Returns an Image object or false if creation fails.
     * This method can only be called from within the class on successful upload.
     */
    public function createImage($id, $extension, $imageCaption, $imageType, $imageSize, $imageUploadDate, $userId) {
        // Sanitize user input to prevent SQL injection
        $id = htmlspecialchars($id);
        $extension = htmlspecialchars($extension);
        $imageCaption = htmlspecialchars($imageCaption);
        $imageType = htmlspecialchars($imageType);
        $imageSize = htmlspecialchars($imageSize);
        $imageUploadDate = htmlspecialchars($imageUploadDate);
        $userId = htmlspecialchars($userId);

        // Insert new image into the database
        $insertImageQuery = "INSERT INTO images (id, extension, image_caption, image_type, image_size, image_upload_date, user_id) VALUES (:id, :extension, :imageCaption, :imageType, :imageSize, :imageUploadDate, :userId)";
        $stmt = $this->db->prepare($insertImageQuery);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':extension', $extension);
        $stmt->bindParam(':imageCaption', $imageCaption);
        $stmt->bindParam(':imageType', $imageType);
        $stmt->bindParam(':imageSize', $imageSize);
        $stmt->bindParam(':imageUploadDate', $imageUploadDate);
        $stmt->bindParam(':userId', $userId);

        if ($stmt->execute()) {
            // Image creation successful
            return new Image($id, $extension, $imageCaption, $imageType, $imageSize, $imageUploadDate, $userId);
        } else {
            // Image creation failed
            return false;
        }
    }

    /*
     * Returns an Album object or false if creation fails.
     */
    public function createAlbum($albumName, $description, $userId) {
        // Sanitize user input to prevent SQL injection
        $albumName = htmlspecialchars($albumName);
        $description = htmlspecialchars($description);
        $userId = htmlspecialchars($userId);

        // Insert new album into the database
        $insertImageQuery = "INSERT INTO albums (album_name, description, user_id) VALUES (:albumName, :description, :userId)";
        $stmt = $this->db->prepare($insertImageQuery);
        $stmt->bindParam(':albumName', $albumName);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':userId', $userId);

        if ($stmt->execute()) {
            // Album creation successful
            return new Image($albumName, $description, $userId);
        } else {
            // Album creation failed
            return false;
        }
    }

    /*
     * Returns an album or false if the album is not found
     */
    public function getAlbumByName($albumName, $userId) {
        // Sanitize user input to prevent SQL injection
        $albumName = htmlspecialchars($albumName);
        $userId = htmlspecialchars($userId);

        // Get image from the database
        $getAlbumQuery = "SELECT * FROM images WHERE album_name = :albumName AND user_id = :userId";
        $stmt = $this->db->prepare($getAlbumQuery);
        $stmt->bindParam(':albumName', $albumName);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $album = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($album) {
            // album found
            return new Album($album['album_name'], $album['description'], $album['user_id']);
        } else {
            // album not found
            return false;
        }
    }

    /*
     * Returns an Image object/Image array or false if the image(s) is not found
     */
    public function getImageById($ids, $userId) {
        // Sanitize user input to prevent SQL injection
        $id = htmlspecialchars($ids);

        // Get image from the database
        $getImageQuery = "SELECT * FROM images WHERE id IN(:id) AND user_id = :userId";
        $stmt = $this->db->prepare($getImageQuery);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($images) {
            // Image(s) found
            if (count($images) > 1) {
                $imageObjects = array();
                foreach ($images as $image) {
                    $imageObjects[] = new Image($image['id'], $image['extension'], $image['image_caption'], $image['image_type'], $image['image_size'], $image['image_upload_date'], $image['user_id']);
                }
                return $imageObjects;
            }
            $image = $images[0];
            return new Image($image['id'], $image['extension'], $image['image_caption'], $image['image_type'], $image['image_size'], $image['image_upload_date'], $image['user_id']);
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
     * Returns an array of Image objects or false if no images are found
     */
    // TODO: add params for pagination
    public function getAllImages($userId) {
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
                $imageObjects[] = new Image($image['id'], $image['extension'], $image['image_caption'], $image['image_type'], $image['image_size'], $image['image_upload_date'], $image['user_id']);
            }
            return $imageObjects;
        } else {
            // No images not found
            return false;
        }
    }

    /*
     * Returns an array of Album objects or false if no albums are found
     */
    // TODO: add params for pagination
    public function getAllAlbums($userId) {
        // Get albums from the database
        $getAlbumsQuery = "SELECT * FROM albums WHERE user_id = :userId";
        $stmt = $this->db->prepare($getAlbumsQuery);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($albums) {
            // albums found
            $albumObjects = array();
            foreach ($albums as $image) {
                $albumObjects[] = new Album($image['album_name'], $image['description'], $image['user_id']);
            }
            return $albumObjects;
        } else {
            // No images not found
            return false;
        }
    }

    /*
     * Returns an array of Image objects or false if no images are found
     */
    // TODO: add params for pagination
    public function getImagesForAlbum($albumName, $userId) {
        // Sanitize user input to prevent SQL injection
        $albumName = htmlspecialchars($albumName);
        $userId = htmlspecialchars($userId);

        // Get images from the database
        $getImagesQuery = "SELECT * FROM images AS i JOIN album_images AS a ON i.id = a.image_id WHERE a.album_name = :albumName AND i.user_id = :userId";
        $stmt = $this->db->prepare($getImagesQuery);
        $stmt->bindParam(':albumName', $albumName);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($images) {
            // Images found
            $imageObjects = array();
            foreach ($images as $image) {
                $imageObjects[] = new Image($image['id'], $image['extension'], $image['image_caption'], $image['image_type'], $image['image_size'], $image['image_upload_date'], $image['user_id']);
            }
            return $imageObjects;
        } else {
            // No images not found
            return false;
        }
    }

    /*
     * Returns an true if the image was updated, false otherwise
     */
    public function updateImage($image, $userId) {
        // Sanitize user input to prevent SQL injection
        $id = htmlspecialchars($image->id);
        $imageCaption = htmlspecialchars($image->imageCaption);
        // $imageType = htmlspecialchars($image->imageType);
        // $imageSize = htmlspecialchars($image->imageSize);
        // $imageUploadDate = htmlspecialchars($image->imageUploadDate);
        $userId = htmlspecialchars($image->userId);

        // Update image in the database
        $updateImageQuery = "UPDATE images SET image_caption = :imageCaption WHERE id = :id AND user_id = :userId"; //, image_type = :imageType, image_size = :imageSize, image_upload_date = :imageUploadDate, user_id = :userId 
        $stmt = $this->db->prepare($updateImageQuery);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':imageCaption', $imageCaption);
        // $stmt->bindParam(':imageType', $imageType);
        // $stmt->bindParam(':imageSize', $imageSize);
        // $stmt->bindParam(':imageUploadDate', $imageUploadDate);
        $stmt->bindParam(':userId', $userId);

        if ($stmt->execute()) {
            // Image update successful
            return true; //new Image($filename, $imageCaption, $imageType, $imageSize, $imageUploadDate, $userId);
        } else {
            // Image update failed
            return false;
        }
    }

    /*
     * Returns true if the image is deleted or false if the image is not found.
     * Deletes the file from the server and the database.
     */
    public function deleteImage($ids, $userId) {
        // Delete image from the database
        if (is_array($ids)) {
            $ids = array_map('htmlspecialchars', $ids);
            $ids = implode(',', $ids);
        } else {
            $ids = htmlspecialchars($ids);
        }

        $deleteImageQuery = "DELETE FROM images WHERE id IN(:ids) AND user_id = :userId";
        $stmt = $this->db->prepare($deleteImageQuery);
        $stmt->bindParam(':ids', $ids);
        $stmt->bindParam(':userId', $userId);
        return $stmt->execute();
    }

    /*
     * Returns true if the images are deleted or false if no images are found.
     * Deletes the files from the server and the database.
     */
    public function deleteImagesFromAlbum($albumName, $userId, $fromStorage = false) {
        // Sanitize user input to prevent SQL injection
        $albumName = htmlspecialchars($albumName);
        $fromStorage = !!$fromStorage;

        if (!$fromStorage) {
            // Delete images from the database album_images table
            $deleteImagesQuery = "DELETE FROM album_images as ai INNER JOIN albums as a using(album_name) WHERE ai.album_name = :albumName AND a.user_id = :userId";
            $stmt = $this->db->prepare($deleteImagesQuery);
            $stmt->bindParam(':albumName', $albumName);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();

            // Images deleted from album successful
            return true;
        } else {
            // Get image filenames from the database
            $getImageFilenamesQuery = "SELECT ai.image_id FROM album_images as ai INNER JOIN albums as a using(album_name) WHERE ai.album_name = :albumName AND a.user_id = :userId";
            $stmt = $this->db->prepare($getImageFilenamesQuery);
            $stmt->bindParam(':albumName', $albumName);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            $imageFilenames = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($imageFilenames) {
                // Image filenames found, delete the files from the server
                foreach ($imageFilenames as $imageFilename) {
                    $imagePath = $this->uploadPath . $imageFilename['filename'];

                    if (file_exists($imagePath)) {
                        if (!unlink($imagePath)) {
                            return false;
                            // Delete images from any and all albums in the database
                            // $deleteImagesQuery = "DELETE FROM album_images WHERE filename = :filename";
                            // $stmt = $this->db->prepare($deleteImagesQuery);
                            // $stmt->bindParam(':filename', $imageFilename['filename']);
                            // $stmt->execute();
                        } // else {
                        //     return false;
                        // }
                    } // else {
                    //     // File doesn't exist
                    //     // Delete images from any and all albums in the database
                    //     $deleteImagesQuery = "DELETE FROM album_images WHERE filename = :filename";
                    //     $stmt = $this->db->prepare($deleteImagesQuery);
                    //     $stmt->bindParam(':filename', $imageFilename['filename']);
                    //     $stmt->execute();
                    // }

                    // Delete image from the database
                    $deleteImagesQuery = "DELETE FROM images WHERE filename = :filename AND user_id = :userId";
                    $stmt = $this->db->prepare($deleteImagesQuery);
                    $stmt->bindParam(':filename', $imageFilename['filename']);
                    $stmt->bindParam(':userId', $userId);
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
}
