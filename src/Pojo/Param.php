<?php

namespace Mwenju\Common\Pojo;

class Param
{
    /**
     * @var User
     */
   private  $user;

    /**
     * @var array
     */
   private $map;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @param array $map
     */
    public function setMap(array $map)
    {
        $this->map = $map;
    }

}