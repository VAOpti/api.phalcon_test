<?php
namespace MyApp\Models;

use Phalcon\Mvc\Model;
use MyApp\Models\Rooms;

class Houses extends Model
{
    public $house_id;
    public $owner_id;
    public $street;
    public $number;
    public $addition;
    public $zipCode;
    public $city;

    public function initialize()
    {
        $this->setSource('houses');

        $this->hasMany(
            'house_id',
            Rooms::class,
            'house_id'
        );
    }
}