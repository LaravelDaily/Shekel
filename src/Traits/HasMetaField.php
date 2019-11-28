<?php


namespace Shekel\Traits;


/**
 * Trait HasMetaField
 * @package Shekel\Traits
 *
 * @property object $meta
 */
trait HasMetaField
{

    /**
     * @return object
     */
    public function getMetaAttribute($value)
    {
        try {
            return json_decode($value);
        } catch (\Exception $e) {
            return (object)[];
        }
    }

    /**
     * @return object
     */
    public function setMetaAttribute($value)
    {
        //IF A STRING IS PASSED ALONG WE IMPLY THAT IT IS A JSON STRING
        if (is_string($value)) {
            try {
                json_decode($value);
            } catch (\Exception $e) {
                throw new \Exception('Invalid json string passed as meta field.');
            }
            $this->attributes['meta'] = $value;
        } else {
            $this->attributes['meta'] = json_encode($value);
        }
    }

    /**
     * @param $key
     * @param $value
     * @return HasMetaField
     */
    public function setMeta($key, $value): self
    {
        $meta = $this->meta ?? (object)[];

        $meta = data_set($meta, $key, $value);

        $this->meta = $meta;

        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getMeta($key)
    {
        return data_get($this->meta, $key, null);
    }

}