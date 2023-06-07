<?php

namespace App\Interfaces;
interface BaseRepository
{
    public function search():void;

    public function create():void;

    public function register($command,$param):void;
}
