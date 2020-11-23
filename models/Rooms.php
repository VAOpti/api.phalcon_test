<?php
namespace MyApp\Models;

use Phalcon\Mvc\Model;
use MyApp\Models\Houses;

class Rooms extends Model
{
    public $room_id;
    public $owner_id;
    public $house_id;
    public $type;
    public $width;
    public $length;
    public $height;

    public function initialize()
    {
        $this->setSource('rooms');

        $this->belongsTo(
            'house_id',
            Houses::class,
            'house_id'
        );
    }
}