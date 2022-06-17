<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: user.service.proto

namespace User;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>user.UserStatus</code>
 */
class UserStatus extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>int64 isLock = 1;</code>
     */
    protected $isLock = 0;
    /**
     * Generated from protobuf field <code>int64 isShield = 2;</code>
     */
    protected $isShield = 0;
    /**
     * Generated from protobuf field <code>string nickname = 3;</code>
     */
    protected $nickname = '';
    /**
     * Generated from protobuf field <code>string avatar = 4;</code>
     */
    protected $avatar = '';
    /**
     * Generated from protobuf field <code>string sex = 5;</code>
     */
    protected $sex = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $isLock
     *     @type int|string $isShield
     *     @type string $nickname
     *     @type string $avatar
     *     @type string $sex
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\UserService::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>int64 isLock = 1;</code>
     * @return int|string
     */
    public function getIsLock()
    {
        return $this->isLock;
    }

    /**
     * Generated from protobuf field <code>int64 isLock = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setIsLock($var)
    {
        GPBUtil::checkInt64($var);
        $this->isLock = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 isShield = 2;</code>
     * @return int|string
     */
    public function getIsShield()
    {
        return $this->isShield;
    }

    /**
     * Generated from protobuf field <code>int64 isShield = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setIsShield($var)
    {
        GPBUtil::checkInt64($var);
        $this->isShield = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string nickname = 3;</code>
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Generated from protobuf field <code>string nickname = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setNickname($var)
    {
        GPBUtil::checkString($var, True);
        $this->nickname = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string avatar = 4;</code>
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Generated from protobuf field <code>string avatar = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setAvatar($var)
    {
        GPBUtil::checkString($var, True);
        $this->avatar = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string sex = 5;</code>
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Generated from protobuf field <code>string sex = 5;</code>
     * @param string $var
     * @return $this
     */
    public function setSex($var)
    {
        GPBUtil::checkString($var, True);
        $this->sex = $var;

        return $this;
    }

}

