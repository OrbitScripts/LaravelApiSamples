<?php

namespace App\Models\Plain;


use App\Helper;
use App\Models\AvtocodMark;
use App\Models\AvtocodModel;
use App\Models\DefaultTS;

class LicensePlateInfo {
    /**
     * @var $vin string
     */
    public $vin;
    /**
     * @var $body string
     */
    public $body;
    /**
     * @var $sts string
     */
    public $sts;
    /**
     * @var $stsType string
     */
    public $stsType;
    /**
     * @var $stsDate string
     */
    public $stsDate;
    /**
     * @var $brandOriginalName string
     */
    public $brandOriginalName;
    /**
     * @var $brandNormalizedName string
     */
    public $brandNormalizedName;
    /**
     * @var $brandId string
     */
    public $brandId;
    /**
     * @var string $brandDefault
     */
    public $brandDefault;
    /**
     * @var $modelOriginalName string
     */
    public $modelOriginalName;
    /**
     * @var $modelNormalizedName string
     */
    public $modelNormalizedName;
    /**
     * @var $modelId string
     */
    public $modelId;
    /**
     * @var $modelDefault string
     */
    public $modelDefault;
    /**
     * @var $age int
     */
    public $age;
    /**
     * @var $year int
     */
    public $year;
    /**
     * @var $power double
     */
    public $power;
    /**
     * @var $weightMax int
     */
    public $weightMax;
    /**
     * @var $category string
     */
    public $category;

    /**
     * LicensePlateInfo constructor.
     * @param object $data
     */
    public function __construct(object $data) {
        if (isset($data->identifiers) && isset($data->identifiers->vehicle)) {
            $this->vin = $data->identifiers->vehicle->vin ?? '';
            $this->body = $data->identifiers->vehicle->body ?? '';
            $this->sts = $data->identifiers->vehicle->sts ?? '';
            $this->stsType = Helper::detectStsType($this->sts);
        }
        if (isset($data->tech_data)) {
            if (isset($data->tech_data->brand)) {
                $this->brandOriginalName = $data->tech_data->brand->name->original ?? '';
                $this->brandNormalizedName = $data->tech_data->brand->name->normalized ?? '';
                $this->brandId = $data->tech_data->brand->id ?? '';
            }
            if (isset($data->tech_data->model)) {
                $this->modelOriginalName = $data->tech_data->model->name->original ?? '';
                $this->modelNormalizedName = $data->tech_data->model->name->normalized ?? '';
                $this->modelId = $data->tech_data->model->id ?? '';
            }
            $this->year = $data->tech_data->year ?? null;
            if ($this->year) {
                $this->age = date('Y') - $this->year;
            }
            $this->power = isset($data->tech_data->engine) && isset($data->tech_data->engine->power) &&
                $data->tech_data->engine->power->hp ? round($data->tech_data->engine->power->hp) : null;
            $this->weightMax = $data->tech_data->weight->max ?? null;
        }
        $this->category = $data->additional_info->vehicle->category->code ?? '';

        if ($this->modelId) {
            $model = AvtocodModel::whereAvtocodModelId($this->modelId)->first();
            if ($model) {
                $model = DefaultTS::whereId($model->default_id)->first();
                if ($model) {
                    $this->modelDefault = $model->model;
                    $this->brandDefault = $model->mark;
                }
            }
        }
        if (!isset($model) && $this->brandId) {
            $mark = AvtocodMark::whereAvtocodMarkId($this->brandId)->first();
            if ($mark) {
                $this->brandDefault = $mark->default_mark;
            }
        }
    }

    /**
     * Строка с полями объекта
     */
    public function toString() {
        echo 'Info: ' . PHP_EOL .
            'Original name: ' . $this->brandOriginalName . PHP_EOL .
            'Normalized name: ' . $this->brandNormalizedName . PHP_EOL .
            'brand ID: ' . $this->brandId . PHP_EOL .
            'Category: ' . $this->category . PHP_EOL .
            'VIN: ' . $this->vin . PHP_EOL .
            'BODY: ' . $this->body . PHP_EOL .
            'STS type: ' . $this->stsType . PHP_EOL .
            'STS date: ' . $this->stsDate . PHP_EOL .
            'STS: ' . $this->sts . PHP_EOL .
            'Year: ' . $this->year . PHP_EOL .
            'Age: ' . $this->age . PHP_EOL .
            'Power: ' . $this->power . PHP_EOL .
            'Max weight: ' . $this->weightMax . PHP_EOL;
    }
}
