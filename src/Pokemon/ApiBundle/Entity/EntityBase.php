<?php
/**************************************************************************
 * EntityBase.php, pokemon Android
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
 * EntityBase
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 * @JSON\ExclusionPolicy("ALL")
 */
abstract class EntityBase
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @var integer
     */
    protected $id;

    /**
     * @JSON\Type("integer")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @JSON\SerializedName("mobileId")
     * @var integer
     */
    private $mobileId;

    /**
     * @ORM\Column(name="sync_udate", type="datetime")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @JSON\SerializedName("sync_uDate")
     * @var \DateTime
     */
    private $sync_uDate;

    /**
     * @ORM\Column(name="sync_dtag", type="boolean")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @JSON\SerializedName("sync_dTag")
     * @var boolean
     */
    private $sync_dTag;

    /**
     * @ORM\Column(name="create_at", type="datetime")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @JSON\SerializedName("createAt")
     * @var \DateTime
     */
    private $createAt;

    /**
     * @ORM\Column(name="update_at", type="datetime", nullable = true)
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @JSON\SerializedName("updateAt")
     * @var \DateTime
     */
    private $updateAt;

    /**
     * @ORM\Column(type="guid")
     * @JSON\Expose
     * @JSON\Groups({"api_process"})
     * @JSON\Since("1.0")
     * @var guid
     */
    private $hash;

    /**
     * Display string of object
     * @return string
     */
    public function __toString()
    {
        return strval($this->id);
    }

    /**
     * Hook on pre-persist operations
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createAt = new \DateTime();
        $this->updateAt = new \DateTime();

        if (!$this->sync_uDate) {
            $this->sync_uDate = new \DateTime();
        }

        if (!$this->sync_dTag) {
            $this->sync_dTag = false;
        }

        if (!$this->hash) {
            $this->hash = EntityBase::generate_uuid();
        }
    }

    /**
     * Hook on pre-update operations
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updateAt = new \DateTime();
    }

    /**
     * Set id
     * @param integer $id
     *
     * @return EntityBase
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set mobileId
     * @param integer $mobileId
     *
     * @return EntityBase
     */
    public function setMobileId($mobileId)
    {
        $this->mobileId = $mobileId;

        return $this;
    }

    /**
     * Get mobileId
     * @return integer
     */
    public function getMobileId()
    {
        return $this->mobileId;
    }

    /**
     * Set syncUDate
     * @param \DateTime $syncUDate
     *
     * @return EntityBase
     */
    public function setSyncUDate($syncUDate)
    {
        $this->sync_uDate = $syncUDate;

        return $this;
    }

    /**
     * Get syncUDate
     * @return \DateTime
     */
    public function getSyncUDate()
    {
        return $this->sync_uDate;
    }

    /**
     * Set syncDTag
     * @param boolean $syncDTag
     *
     * @return EntityBase
     */
    public function setSyncDTag($syncDTag)
    {
        $this->sync_dTag = $syncDTag;

        return $this;
    }

    /**
     * Get syncDTag
     * @return boolean
     */
    public function getSyncDTag()
    {
        return $this->sync_dTag;
    }

    /**
     * Set createAt
     * @param \DateTime $createAt
     *
     * @return EntityBase
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
     * @return EntityBase
     */
    public function setUpdateAt($updateAt)
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    /**
     * Get updateAt
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
    * Set hash
    * @param UUID $hash
    *
    * @return EntityBase
    */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     * @return UUIS
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Generate uuid
     * @return uuid
     */
    public static function generate_uuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ));
    }
}
