<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/1/11
 */

namespace App\Http\Controllers\Api\V3\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class NoDataArraySerializer extends ArraySerializer
{
    /**
     * Serialize a collection.
     */
    public function collection($resourceKey, array $data)
    {
        return ($resourceKey) ? [ $resourceKey => $data ] : $data;
    }

    /**
     * Serialize an item.
     */
    public function item($resourceKey, array $data)
    {
        return ($resourceKey) ? [ $resourceKey => $data ] : $data;
    }
}
