<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Stdlib\Exception;
use Dboss\PasswordHash;
use Dboss\Xtea;

/** @ORM\Entity @ORM\HasLifecycleCallbacks */
class User extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", nullable=false)
     **/
    protected $user_id;

    /** @ORM\Column(type="string", unique=true, nullable=false) */
    protected $user_name;

    /** @ORM\Column(type="string", nullable=false) */
    protected $first_name;

    /** @ORM\Column(type="string", nullable=false) */
    protected $last_name;

    /** @ORM\Column(type="string", nullable=false) */
    protected $password;

    /** @ORM\Column(type="datetime", nullable=false) */
    protected $creation_date;

    /** @ORM\Column(type="datetime", nullable=false) */
    protected $modification_date;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $deletion_date;

    /**
     * @ORM\ManyToOne(targetEntity="Role")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="role_id")
     **/
    protected $role;

    /**
     * @ORM\OneToMany(targetEntity="Query", mappedBy="user")
     **/
    protected $queries;

    /**
     * @ORM\OneToMany(targetEntity="Server", mappedBy="user")
     **/
    protected $servers;

    /**
     * @ORM\OneToMany(targetEntity="Database", mappedBy="user")
     **/
    protected $databases;

    protected $security;

    /**
     *
     **/
    public function __construct()
    {
        $this->queries = new ArrayCollection();
        $this->servers = new ArrayCollection();
        $this->databases = new ArrayCollection();
    }

    /**
     * Magic setter
     **/
    public function __set($field_name, $value)
    {
        switch ($field_name) {
            case "password":
                $this->setPassword($value);
                break;
            case "user_name":
                $this->setUserName($value);
                break;
            case "security":
                $this->setSecurity($value);
                break;
            default:
                $this->$field_name = $value;
                break;
        }
    }

    /**
     * Magic getter
     **/
    public function __get($field_name)
    {
        switch ($field_name) {
            case "user_name":
                return $this->getUserName();
                break;
            default:
                return $this->$field_name;
                break;
        }
    }

    /**
     * 
     **/
    public function setPassword($password)
    {
        $salt_key = null;
        $iteration_count = null;
        $portable_hashes = null;

        extract($this->security, EXTR_IF_EXISTS);

        $hasher = new PasswordHash($iteration_count, $portable_hashes);
        $this->password = $hasher->HashPassword($salt_key.$password);
    }

    /**
     *
     **/
    public function setUserName($user_name)
    {
        $xtea = new Xtea($this->security['salt_key']);
        $this->user_name = $xtea->encrypt($user_name);
    }

    /**
     * 
     **/
    public function getUserName()
    {
        $xtea = new Xtea($this->security['salt_key']);
        return $xtea->decrypt($this->user_name);
    }

    /**
     * 
     **/
    public function getRawUserName()
    {
        return $this->user_name;
    }

    /**
     * 
     **/
    public function setSecurity(array $security = array())
    {
        $salt_key = null;
        $iteration_count = 8;
        $portable_hashes = 0;

        extract($security, EXTR_IF_EXISTS);

        if ( ! $salt_key) {
            throw new Exception\BadMethodCallException('Missing salt_key. Invalid security option passed to ' . __METHOD__);
        }

        $this->security = array(
            'salt_key'        => $salt_key,
            'iteration_count' => $iteration_count,
            'portable_hashes' => $portable_hashes,
        );
    }

    /**
     * Checks to see if a query belongs to the user
     * 
     * @param int A query_id
     * 
     * @return boolean
     **/
    public function isMyQuery($query_id = null)
    {
        if (! $this->user_id || ! is_numeric($this->user_id)) {
            return false;
        }

        if (! $query_id || ! is_numeric($query_id)) {
            return false;
        }

        $criteria = new Criteria();

        $criteria->andWhere(
            $criteria->expr()->eq(
                'user_id',
                $this->user_id
            )
        );

        $criteria->andWhere(
            $criteria->expr()->eq(
                'query_id',
                $query_id
            )
        );

        $query = $this->queries->matching($criteria);

        return ($query->count()) ? true : false;
    }

    /**
     * Checks to see if a database belongs to the user
     * 
     * @param int A database_id
     * 
     * @return boolean
     **/
    public function isMyDatabase($database_id = null)
    {
        if (! $this->user_id || ! is_numeric($this->user_id)) {
            return false;
        }

        if (! $database_id || ! is_numeric($database_id)) {
            return false;
        }

        $criteria = new Criteria();

        $criteria->andWhere(
            $criteria->expr()->eq(
                'user_id',
                $this->user_id
            )
        );

        $criteria->andWhere(
            $criteria->expr()->eq(
                'database_id',
                $database_id
            )
        );

        $database = $this->databases->matching($criteria);

        return ($database->count()) ? true : false;
    }

    /**
     * Checks to see if a server belongs to the user
     * 
     * @param int A server_id
     * 
     * @return boolean
     **/
    public function isMyServer($server_id = null)
    {
        if ( ! $this->user_id || ! is_numeric($this->user_id)) {
            return false;
        }

        if ( ! $server_id || ! is_numeric($server_id)) {
            return false;
        }

        $criteria = new Criteria();

        $criteria->andWhere(
            $criteria->expr()->eq(
                'user_id',
                $this->user_id
            )
        );

        $criteria->andWhere(
            $criteria->expr()->eq(
                'server_id',
                $server_id
            )
        );

        $server = $this->servers->matching($criteria);

        return ($server->count()) ? true : false;
    }

    /**
     * Gets a user's query (but only if it belongs to the user)
     * 
     * @param int A query_id
     * 
     * @return boolean
     **/
    public function getMyQuery($query_id = null)
    {
        if ( ! $this->user_id || ! is_numeric($this->user_id)) {
            return null;
        }

        if ( ! $query_id || ! is_numeric($query_id)) {
            return null;
        }

        $criteria = new Criteria();

        $criteria->andWhere(
            $criteria->expr()->eq(
                'user_id',
                $this->user_id
            )
        );

        $criteria->andWhere(
            $criteria->expr()->eq(
                'query_id',
                $query_id
            )
        );

        $query = $this->queries->matching($criteria);

        return ($query->count()) ? $query->first() : null;
    }

    /**
     * See if this user is a "Boss" (Admin)
     **/
    public function isaBoss()
    {
        if (! $this->role) {
            return false;
        }

        return ($this->role->role_name == "boss") ? true : false;
    }

    /**
     *  Checks to see if the user has limited access
     **/
    public function isLimited()
    {
        if (! $this->role) {
            return true;
        }

        return ($this->role->role_name == "limited") ? true : false;
    }

    /**
     * Check to see if user can edit certain data connected to a specific user_id
     **/
    public function canEditUser($user_id)
    {
        if (! $user_id) {
            return false;
        }

        return ($this->isaBoss() || $user_id == $this->user_id);
    }
}