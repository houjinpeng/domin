<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models;

use AlibabaCloud\Tea\Model;

class SaveRegistrantProfileRealNameVerificationResponseBody extends Model
{
    /**
     * @example 1234567
     *
     * @var int
     */
    public $registrantProfileId;

    /**
     * @example 4D73432C-7600-****-ACBB-C3B5CA145D32
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'registrantProfileId' => 'RegistrantProfileId',
        'requestId'           => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->registrantProfileId) {
            $res['RegistrantProfileId'] = $this->registrantProfileId;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SaveRegistrantProfileRealNameVerificationResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RegistrantProfileId'])) {
            $model->registrantProfileId = $map['RegistrantProfileId'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
