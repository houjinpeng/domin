<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models;

use AlibabaCloud\Tea\Model;

class SaveBatchTaskForModifyingDomainDnsResponseBody extends Model
{
    /**
     * @example 6A862A8A-E7AB-4C4E-8946-A74122D9CC4B
     *
     * @var string
     */
    public $requestId;

    /**
     * @example 35fb2fb7-d4d6-4478-9408-22cb63696b86
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
     * @return SaveBatchTaskForModifyingDomainDnsResponseBody
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
