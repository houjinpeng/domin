<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Domain\V20180129\Models\ScrollDomainListResponseBody\data\domain;

use AlibabaCloud\Tea\Model;

class tag extends Model
{
    /**
     * @var \AlibabaCloud\SDK\Domain\V20180129\Models\ScrollDomainListResponseBody\data\domain\tag\tag[]
     */
    public $tag;
    protected $_name = [
        'tag' => 'Tag',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->tag) {
            $res['Tag'] = [];
            if (null !== $this->tag && \is_array($this->tag)) {
                $n = 0;
                foreach ($this->tag as $item) {
                    $res['Tag'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return tag
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Tag'])) {
            if (!empty($map['Tag'])) {
                $model->tag = [];
                $n          = 0;
                foreach ($map['Tag'] as $item) {
                    $model->tag[$n++] = null !== $item ? \AlibabaCloud\SDK\Domain\V20180129\Models\ScrollDomainListResponseBody\data\domain\tag\tag::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
