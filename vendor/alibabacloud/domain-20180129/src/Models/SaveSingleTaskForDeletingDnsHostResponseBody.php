<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models;

use AlibabaCloud\Tea\Model;

class SaveSingleTaskForDeletingDnsHostResponseBody extends Model
{
    /**
     * @example 8fc97e44-837a-447d-ac61-ea28d2fe8a38
     *
     * @var string
     */
    public $requestId;

    /**
     * @example 8fc97e44-837a-447d-ac61-ea28d2fexxxx
     *
     * @var string
     */
    public $taskNo;
    protected $_name = [
        'requestId' => 'RequestId',
        'taskNo'    => 'TaskNo',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->taskNo) {
            $res['TaskNo'] = $this->taskNo;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SaveSingleTaskForDeletingDnsHostResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['TaskNo'])) {
            $model->taskNo = $map['TaskNo'];
        }

        return $model;
    }
}
