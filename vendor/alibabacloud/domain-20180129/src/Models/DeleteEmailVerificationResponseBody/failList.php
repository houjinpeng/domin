<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models\DeleteEmailVerificationResponseBody;

use AlibabaCloud\Tea\Model;

class failList extends Model
{
    /**
     * @example ParameterIllegall
     *
     * @var string
     */
    public $code;

    /**
     * @example test1@aliyun.com
     *
     * @var string
     */
    public $email;

    /**
     * @example Parameter error
     *
     * @var string
     */
    public $message;
    protected $_name = [
        'code'    => 'Code',
        'email'   => 'Email',
        'message' => 'Message',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->email) {
            $res['Email'] = $this->email;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return failList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['Email'])) {
            $model->email = $map['Email'];
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }

        return $model;
    }
}
