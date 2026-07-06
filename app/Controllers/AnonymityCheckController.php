<?php

require_once __DIR__ . '/AnonymityCheck/AnonymityGeoApiTrait.php';
require_once __DIR__ . '/AnonymityCheck/AnonymitySaveTrait.php';
require_once __DIR__ . '/AnonymityCheck/AnonymityIndexTrait.php';

class AnonymityCheckController extends Controller
{
    use AnonymityGeoApiTrait, AnonymitySaveTrait, AnonymityIndexTrait;
}
