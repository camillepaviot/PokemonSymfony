<?php

/**************************************************************************
 * EntityResourceBase.php, pokemon Android
 *
 * Copyright 2016
 * Description : 
 * Author(s)   : Harmony
 * Licence     : 
 * Last update : May 26, 2016
 *
 **************************************************************************/

namespace Pokemon\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JSON;
use \DateTime;

/**
 * EntityResource
 * @ORM\MappedSuperclass
 * @ORM\Table(name="pokemon_resource")
 * @ORM\InheritanceType ("SINGLE_TABLE")
 * @ORM\Entity(repositoryClass="Pokemon\ApiBundle\Repository\EntityResourceRepository")
 * @JSON\ExclusionPolicy("ALL")
 */
abstract class EntityResourceBase extends EntityBase
{

    /**
     * @ORM\Column(type="string")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @var string
     */
    protected $path;

    /**
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @var string
     */
    protected $originalpath;

    protected $file;

    public function getAbsolutePath()
    {
        $imageName = $this->getImageName();

        return null === $imageName ? null : $this->getUploadRootDir() . '/' . $imageName;
    }

    public function getWebPath()
    {
        $imageName = $this->getImageName();

        return null === $imageName ? null : $this->getUploadDir() . '/' . $imageName;
    }

    protected function getUploadRootDir($basepath)
    {
        // the absolute directory path where uploaded documents should be saved
        return $basepath . $this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw when displaying uploaded doc/image in the view.
        return 'uploads/images';
    }

    public function upload($basepath)
    {
        // the file property can be empty if the field is not required
        if (null === $this->file) {
            return;
        }

        $extension = $this->file->guessExtension();

        // Check if file must be resize, otherwise move it directly
        if (!$this->resizeImage($this->file, $this->getUploadRootDir($basepath), $extension)) {
            $this->file->move($this->getUploadRootDir($basepath), $this->getImageName());
        }

        $this->file = null;
    }

    /**
     * Generate a new unique name based on the current filename.
     */
    public function initializeName() {
        // Get extension from filename
        $extension = substr(strrchr($this->path, "."), 1);

        if ($extension == "" || strlen($extension) > 4) {
            $extension = "jpg";
        }

        // Generate unique name and add kown extension
        $uniqName = uniqid("", true) . "." . strtolower($extension);

        $this->setPath('/' . $this->getUploadDir() . '/' .$uniqName);
    }

    public function resizeImage($file, $path, $extension)
    {
        $result = false;

        $extensions = array(
            0 => 'jpg',
            1 => 'jpeg',
            2 => 'png'
        );

        $imageSize = getimagesize($file);
        $imageWidth = $imageSize[0];
        $imageHeight = $imageSize[1];
        $handling = null;

        if (in_array(strtolower($extension), $extensions)
            && ($imageWidth > 1024 || $imageHeight > 1024 )) {

            if ($imageWidth > 1024) {
                $handling = "width";
            } elseif($imageHeight > 1024) {
                $handling = "height";
            }

            if ($extension == $extensions[0] ||
                $extension == $extensions[1]
            ){
                $imageSelect = imagecreatefromjpeg($file);
                $type = "jpg";
            } elseif ($extension == $extensions[2]) {
                $imageSelect = imagecreatefrompng($file);
                $type = "png";
            }

            $newWidth = 1024;
            $newHeight = 1024;

            if ( $handling == "width") {
                $newHeight = ( ($imageHeight * (($newWidth) / $imageWidth)) );
            } elseif ($handling == "height") {
                $newWidth = ( ($imageWidth * (($newHeight) / $imageHeight)) );
            }

            $newImage = imagecreatetruecolor($newWidth, $newHeight) or die("Erreur");

            imagecopyresampled($newImage, $imageSelect,
                0, 0, 0, 0,
                $newWidth, $newHeight, $imageSize[0], $imageSize[1]);

            if ($type == "jpg") {
                imagejpeg($newImage, $path.'/'. $this->getImageName(), 80);
            } elseif ($type == "png") {
                imagepng($newImage, $path .'/'. $this->getImageName());
            }

            imagedestroy($imageSelect);

            $result = true;
        }

        return $result;
    }

    /**
     * Display string of object
     * @return string
     */
    public function __toString()
    {
        return strval($this->path);
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createAt
     * @param \DateTime $createAt
     *
     * @return Image
     */
    public function setCreateAt($createAt)
    {
        $this->createAt = $createAt;

        return $this;
    }

    /**
     * Get createAt
     * @return \DateTime
     */
    public function getCreateAt()
    {
        return $this->createAt;
    }

    /**
     * Set updateAt
     * @param \DateTime $updateAt
     *
     * @return Image
     */
    public function setUpdateAt($updateAt)
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    /**
     * Get updateAt
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * Set path
     * @param string $path
     *
     * @return Image
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get originalpath
     * @return string
     */
    public function getOriginalpath()
    {
        return $this->originalpath;
    }

    /**
     * Set originalpath
     * @param string $originalpath
     *
     * @return Image
     */
    public function setOriginalpath($originalpath)
    {
        $this->originalpath = $originalpath;

        return $this;
    }

    /**
     * Get path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get file
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     * @param string $file
     *
     * @return File
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     * @return string
     */
    public function getImageName()
    {
        return basename($this->path);
    }

    /**
     * Hook on pre-persist operations
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        parent::prePersist();
        $this->initializeName();
    }
}