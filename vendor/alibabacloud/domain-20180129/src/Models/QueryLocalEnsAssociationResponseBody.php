<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models;

use AlibabaCloud\Tea\Model;

class QueryLocalEnsAssociationResponseBody extends Model
{
    /**
     * @example 3ECD5439-39A2-477D-9A19-64FCA1F77EEB
     *
     * @var string
     */
    public $address;

    /**
     * @example 0x1234567890123456789012345678901234567890
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'address'   => 'Address',
        'requestId' => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->address) {
            $res['Address'] = $this->address;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryLocalEnsAssociationResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Address'])) {
            $model->address = $map['Address'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
