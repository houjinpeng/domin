<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models;

use AlibabaCloud\Tea\Model;

class CheckProcessingServerLockApplyResponseBody extends Model
{
    /**
     * @example true
     *
     * @var bool
     */
    public $exists;

    /**
     * @example 9DFCF6F8-243C-****-8035-4B12FEFD7D48
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'exists'    => 'Exists',
        'requestId' => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->exists) {
            $res['Exists'] = $this->exists;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return CheckProcessingServerLockApplyResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Exists'])) {
            $model->exists = $map['Exists'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
