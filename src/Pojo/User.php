<?php


namespace Mwenju\Common\Pojo;


class User
{
    private $shop_id = 0;
    private $user_id = 0;
    private $admin_id = 0;
    private $supplier_id = 0;
    private $supplier_name = "";
    private $admin_name = "";
    private $admin_depot_id = 1;
    private $admin_role_id = 0;
    private $mobile = '';
    private $province_code = '';
    private $city_code = '';
    private $area_code = '';
    private $depot_id = 1;
    private $device_type = 'weixin';
    private $device_code = '';
    private $shop_info = [];
    private $user_info = [];
    private $shop_name = '';
    private $tag = '';

    /**
     * @return int
     */
    public function getSupplierId(): int
    {
        return $this->supplier_id;
    }

    /**
     * @param int $supplier_id
     */
    public function setSupplierId(int $supplier_id): void
    {
        $this->supplier_id = $supplier_id;
    }

    /**
     * @return string
     */
    public function getSupplierName(): string
    {
        return $this->supplier_name;
    }

    /**
     * @param string $supplier_name
     */
    public function setSupplierName(string $supplier_name): void
    {
        $this->supplier_name = $supplier_name;
    }

    /**
     * @return string
     */
    public function getShopName(): string
    {
        return $this->shop_name;
    }

    /**
     * @param string $shop_name
     */
    public function setShopName(string $shop_name): void
    {
        $this->shop_name = $shop_name;
    }

    /**
     * @return int
     */
    public function getAdminRoleId(): int
    {
        return $this->admin_role_id;
    }

    /**
     * @param int $admin_role_id
     */
    public function setAdminRoleId(int $admin_role_id): void
    {
        $this->admin_role_id = $admin_role_id;
    }

    /**
     * @return int
     */
    public function getAdminDepotId(): int
    {
        return $this->admin_depot_id;
    }

    /**
     * @param int $admin_depot_id
     */
    public function setAdminDepotId(int $admin_depot_id): void
    {
        $this->admin_depot_id = $admin_depot_id;
    }

    /**
     * @return string
     */
    public function getAdminName(): string
    {
        return $this->admin_name;
    }

    /**
     * @param string $admin_name
     */
    public function setAdminName(string $admin_name): void
    {
        $this->admin_name = $admin_name;
    }

    /**
     * @return int
     */
    public function getAdminId(): int
    {
        return $this->admin_id;
    }

    /**
     * @param int $admin_id
     */
    public function setAdminId(int $admin_id): void
    {
        $this->admin_id = $admin_id;
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return int
     */
    public function getShopId(): int
    {
        return $this->shop_id;
    }

    /**
     * @param int $shop_id
     */
    public function setShopId(int $shop_id): void
    {
        $this->shop_id = $shop_id;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return string
     */
    public function getMobile(): string
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    /**
     * @return string
     */
    public function getProvinceCode(): string
    {
        return $this->province_code;
    }

    /**
     * @param string $province_code
     */
    public function setProvinceCode(string $province_code): void
    {
        $this->province_code = $province_code;
    }

    /**
     * @return string
     */
    public function getCityCode(): string
    {
        return $this->city_code;
    }

    /**
     * @param string $city_code
     */
    public function setCityCode(string $city_code): void
    {
        $this->city_code = $city_code;
    }

    /**
     * @return string
     */
    public function getAreaCode(): string
    {
        return $this->area_code;
    }

    /**
     * @param string $area_code
     */
    public function setAreaCode(string $area_code): void
    {
        $this->area_code = $area_code;
    }

    public function toString():string
    {
        return json_encode($this,JSON_FORCE_OBJECT);
    }

    /**
     * @return int
     */
    public function getDepotId(): int
    {
        return $this->depot_id;
    }

    /**
     * @param int $depot_id
     */
    public function setDepotId(int $depot_id): void
    {
        $this->depot_id = $depot_id;
    }

    /**
     * @return string
     */
    public function getDeviceType(): string
    {
        return $this->device_type;
    }

    /**
     * @param string $device_type
     */
    public function setDeviceType(string $device_type): void
    {
        $this->device_type = $device_type;
    }

    /**
     * @return string
     */
    public function getDeviceCode(): string
    {
        return $this->device_code;
    }

    /**
     * @param string $device_code
     */
    public function setDeviceCode(string $device_code): void
    {
        $this->device_code = $device_code;
    }

    /**
     * @return array
     */
    public function getShopInfo(): array
    {
        return $this->shop_info;
    }

    /**
     * @param array $shop_info
     */
    public function setShopInfo(array $shop_info): void
    {
        $this->shop_info = $shop_info;
    }

    /**
     * @return array
     */
    public function getUserInfo(): array
    {
        return $this->user_info;
    }

    /**
     * @param array $user_info
     */
    public function setUserInfo(array $user_info): void
    {
        $this->user_info = $user_info;
    }
}