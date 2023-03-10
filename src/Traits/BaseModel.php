<?php

namespace Assets\Traits;


use App\Tools\Common\Transformer\Transformer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use \Exception;

/**
 * Class BaseModel
 * @package app\Tools\Common\Models
 */
trait BaseModel
{

    /**
     * @var
     */
    protected $selectStatement = array();

    /**
     * @var array
     */
    private $default_operations = ['=', '<', '>', '>=', '<=', '!=', 'like'];


    /**
     * @var bool
     */
    protected $actionFailed = false;

    /**
     * @var array
     */
    protected $actionMessages = [];


    /**
     * @param array|null $errors
     * @return $this
     */
    public function appendActionErrorMessages(array $errors = null)
    {
        // set status to failed
        $this->setActionFailed();
        // if there was an error sent
        if (is_array($errors)) {
            // iterate trough them
            foreach ($errors as $key => $error) {
                // if the index already exist
                if (isset($this->actionMessages[$key])) {
                    // push the new message
                    array_push($this->actionMessages[$key], $error);
                } else {
                    // create the key and set the error message inside an array
                    array_push($this->actionMessages, [$key => [$error]]);
                }
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function actionFailed()
    {
        return $this->actionFailed;
    }

    /**
     * @param bool $status
     * @return bool
     */
    public function setActionFailed($status = true)
    {
        $this->actionFailed = $status;
        return $this;
    }

    /**
     * @return array
     */
    public function actionMessages()
    {
        return $this->actionMessages;
    }


    /**
     *  Made to be used as in chain
     *  $user = User::all()->transform();
     *
     * @param null $transformerName
     * @return mixed
     */
    public function CustomTransform($transformerName = null)
    {
        // get current array
        $item = $this->convertToArray();
        // get correct transformer
        $transformer = $this->getTransformer($transformerName);
        // return transformer value
        return $this->performTransformation($transformer, $item);
    }

    /**
     * @return array
     */
    protected function convertToArray()
    {
        // get current collection or array
        $item = $this;
        // check if array
        if (is_array($item)) {
            // if true return array
            return $item;
        }
        // else convert to array
        return $item->toArray();
    }

    /**
     * @param $transformerName
     * @return
     */
    public function getTransformer($transformerName)
    {
        return $transformerName;
    }


    /**
     * @param $transformer
     * @param $item
     * @return mixed
     */
    protected function performTransformation(Transformer $transformer, $item)
    {
        // Single object
        return $transformer->transform($item);
    }


    /**
     * Allows to pick resources created in any time frame
     *
     * @param $query
     * @param $start
     * @param $end
     * @return mixed
     */
    public function scopeInTimePeriod($query, $start, $end)
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        return $query->where($this->table . 'created_at', '>=', $start)->where($this->table . 'created_at', '<=', $end);
    }

    /**
     * Allows to pick resources updated in any time frame
     *
     * @param $query
     * @param $start
     * @param $end
     * @return mixed
     */
    public function scopeUpdatedInTimePeriod($query, $start, $end)
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        return $query->where($this->table . 'updated_at', '>=', $start)->where($this->table . 'updated_at', '<=', $end);
    }


    /*
     * GLOBAL SCOPE START
     *
     * These global scopes will apply to all models in
     */

    /**
     * Most resources will have a column of active
     * This scope will allow us to access it.
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        if ($this->activeField) {
            return $query->where($this->activeField, 1);
        }
        return $query;
    }

    /**
     * @param $query
     * @param $with
     * @return mixed
     */
    public function scopeLoadWith($query, $with)
    {
        if ($with) {
            return $query->with($with);
        }
        return $query;
    }

    /**
     * @param $query
     * @param $field
     * @param $order
     */
    public function scopeCustomSort($query, $field, $order)
    {
        $query->SortQueryBy($field, $order);
    }

    /**
     * @param $query
     * @param null $string
     * @param null $order
     * @param bool $throw_exception
     * @return mixed
     * @throws Exception
     */
    public function scopeSortQueryBy($query, $string = null, $order = null, $throw_exception = false)
    {
        // get default values if none are sent
        if (!$order) $order = 'DESC';
        if (!$string) $string = 'created_at';
        // if the string is part of the fillable array
        if (in_array($string, $this->fillable) || $string == 'created_at') {
            // preform order by $string
            $string = $this->table . '.' . $string;
            $query->orderBy($string, $order);
        } else {
            // else look for a custom scope
            if (method_exists($this, 'scope' . $string)) {
                // if found preform the custom sort
                return $query->$string($order);
            }
            // if throw exception if custom order by is not found
            if ($throw_exception) {
                $error = [
                    'sort' => "Sort function $string was not found in " . str_replace('App\\', "", get_class($this)),
                    "message" => "Unable to preform sort by $string"
                ];
                throw new Exception(json_encode($error));
            }
        }
    }

    /**
     * @param $query
     * @param null $string
     * @return mixed
     * @throws Exception
     */
    public function scopeFilter($query, $string = null)
    {
        if (!$string) return $query;
        // if it was found explode it
        $filters = $this->getFilters($string);
        foreach ($filters as $filter) {
            // get filter name and operation
            $filter = $this->filterName($filter);
            $filter_name = $filter['name'];
            // check if the filter name is in the main table
            $original_table_fields = array_merge($this->fillable, ['created_at', 'updated_at', 'id']);
            if (in_array($filter_name, $original_table_fields)) {
                // get the operation and variables
                $filter = $this->filterOperationAndVariables($filter['operation']);
                $filter_operation = $filter['operation'];
                $filter_variables = $filter['variables'];
                if (!isset($filter_variables[0])) {
                    throw new Exception(json_encode(
                        [
                            'message' => 'Filter variable invalid!',
                            'filter' => 'Filter variable was now found or was invalid!'
                        ]));
                }
                // if the filter belongs to the main table
                $table = $this->getTable();
                $query->where($table . '.' . $filter_name, $filter_operation, $filter_variables[0]);
            } else {
                // custom filter
                if (method_exists($this, 'scope' . $filter_name)) {
                    // get variables for filter
                    $filter_variables = $this->customFilterVariables($filter['operation']);
                    // perform filter
                    $query->$filter_name($filter_variables);
                } else {
                    $error = [
                        'Filter' => "Filter function $filter_name was not found n " . str_replace('App\\', "", get_class($this)),
                        "message" => "Unable to preform filter by $filter_name"
                    ];
                    throw new Exception(json_encode($error));
                }
            }
        }

    }


    /**
     * @param $string
     * @return array
     */
    public function getFilters($string)
    {
        $exploded = explode('|', $string);
        return $exploded;
    }

    /**
     * @param $string
     * @return mixed
     */
    public function filterName($string)
    {
        // explode string using the :
        $exploded = explode(':', $string, 2);
        // the first object found will be the name
        $final ['name'] = $exploded[0];
        // the second object found will be operation and variables
        if (isset($exploded[1])) {
            $final ['operation'] = $exploded[1];
        } else {
            $final ['operation'] = null;
        }
        return $final;
    }

    /**
     * @param $string
     * @param bool $throw_exception
     * @return mixed
     * @throws Exception
     */
    public function filterOperationAndVariables($string, $throw_exception = true)
    {
        // explode string to get operation and variables
        $exploded = explode(',', $string, 2);
        $final ['operation'] = $exploded[0];
        // if the operation is not in the default operation array
        // and the throw exception is set to true
        if (!in_array($final ['operation'], $this->default_operations) && $throw_exception) {
            $error = [
                'message' => 'Filter operation is invalid!',
                'Operation' => "operation has to be one in ['" . implode("','", $this->default_operations) . "']"
            ];
            // a new exception will be displayed
            throw  new Exception(json_encode($error));
        }
        // if is it
        // unset the operation from the exploded array
        unset($exploded[0]);
        // collapse the left over variables to avoid having keys on the array
        $variables = [];
        foreach ($exploded as $key => $value) {
            array_push($variables, $value);
        }
        $final ['variables'] = $variables;
        if (sizeof($final ['variables']) < 1 && $throw_exception) {
            $error = [
                'message' => 'Filter variables are invalid!',
                'Variables' => "Variables were not found in the request or are invalid"
            ];
            // a new exception will be displayed
            throw  new Exception(json_encode($error));
        }
        return $final;
    }

    /**
     * @param $string
     * @return array
     */
    public function customFilterVariables($string)
    {
        // explode string to get operation and variables
        $exploded = explode(',', $string);
        return $exploded;
    }


    /**
     * @param Model|null $model
     * @param array $validates
     * @return array
     * @throws Exception
     */
    public function validating(Model $model = null, $validates = [])
    {
        $request = Request::capture();
        // get the request information and make into array
        $content = $request->all();
        // throw exception if not sent
        if (!$content) {
            $errors = [
                "Request" => "Request body is empty!"
            ];
            throw new Exception(json_encode($errors));
        }
        if ($model) {
            $data = $this->validateFromModel($model, $validates);
        } else {
            $data = $this->validateForRequired($validates);
        }
        return $data;
    }

    /**
     * @param $validates
     * @return array
     * @throws Exception
     */
    public function validateForRequired($validates)
    {
        // get all data send in request
        $data = Input::all();
        $rules = [];
        foreach ($validates as $rule) {
            $rules[$rule] = 'required';
        }
        unset($data['validate']);
        $validation = Validator::make($data, $rules);
        // if failed
        if ($validation->fails()) {
            // throw exception
            $errors = $validation->messages()->toArray();
            throw new Exception(json_encode($errors));
        }
        // create empty array to hold data set for validation
        $data_to_validate = [];
        foreach ($rules as $field => $rule) {
            // copy over the data to a final validate array
            $data_to_validate[$field] = $data[$field];
        }
        // make validation
        // return data that passed validation
        return $data_to_validate;
    }


    /**
     * @param Model $model
     * @param array $validates
     * @return array
     * @throws Exception
     */
    public function validateFromModel(Model $model, array $validates)
    {
        // if the validation rules are empty
        if (empty($validates)) {
            // get all of the rules
            $validation_rules = $model->getValidationRules();
        } else {
            // if its not fetch only the rules requested
            $validation_rules = $model->fetchRulesNeeded($validates);
        }
        // get all data send in request
        $data = Input::all();
        $validation = Validator::make($data, $validation_rules);
        // if failed
        if ($validation->fails()) {
            // throw exception
            $errors = $validation->messages()->toArray();
            throw new Exception(json_encode($errors));
        }
        // create empty array to hold data set for validation
        $data_to_validate = [];
        foreach ($validation_rules as $field => $rule) {
            // copy over the data to a final validate array
            $data_to_validate[$field] = $data[$field];
        }
        // make validation
        // return data that passed validation
        return $data_to_validate;
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function getValidationRules()
    {
        $model = $this;
        if (!method_exists($model, 'validationRules')) {
            $errors = [
                "Validation" => "Validation rules don't exist in " . get_class($model) . " model",
            ];
            throw new Exception(json_encode($errors));
        }
        return $model->validationRules();
    }

    /**
     * @param array $rules
     * @return array
     */
    public function fetchRulesNeeded($rules = [])
    {
        $model = $this;
        $final = [];
        foreach ($rules as $rule) {
            if (!method_exists($model, 'validationRules')) {
                $final[$rule] = 'required';
            } else {
                $final[$rule] = $model->validationRules()[$rule];
            }
        }
        return $final;
    }


    /**
     * @param $query
     * @param $variables
     * @throws Exception
     */
    public function scopeSearch($query, $variables)
    {
        if (!isset($variables[0])) {
            $errors = [
                'messages' => "Could not user search filter",
                'search' => "The string sent as variable is invalid"
            ];
            throw new Exception(json_encode($errors));
        }
        $cleaned = $this->clean_strings($variables[0]);
        $strings_exploded = explode('-', $cleaned);
        $table = $this->getTable();
        $query->where(function (Builder $query) use ($strings_exploded, $table) {
            foreach ($strings_exploded as $string) {
                foreach ($this->fillable as $filed) {
                    $query->orWhere($table . '.' . $filed, 'like', "%$string%");
                }
            }
        });
    }

    /**
     * Add a string to the Select Statement
     * @param string $select
     */
    public function appendToSelect(string $select)
    {
        array_push($this->selectStatement, $select);
        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $select
     */
    public function scopeAppendToSelect(Builder $query, string $select)
    {
        $this->appendToSelect($select);
    }

    /**
     * Use at the end to append a final Select Statement
     * @param $query
     * @param bool $raw
     */
    public function scopePullSelectInQuery(Builder $query, $raw = true)
    {
        $selectString = implode(',', $this->selectStatement);
        if ($raw) {
            $query->select(DB::raw($selectString));
        } else {
            $query->select($selectString);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $time
     */
    public function scopeDateFrom(Builder $query, int $time)
    {
        $table = $this->getTable();
        $date = Carbon::createFromTimestamp($time)->timezone('UTC')->toDateTimeString();
        $query->where($table . '.created_at', '>', $date);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $time
     */
    public function scopeDateTo(Builder $query, int $time)
    {
        $table = $this->getTable();
        $date = Carbon::createFromTimestamp($time)->timezone('UTC')->toDateTimeString();
        $query->where($table . '.created_at', '<', $date);
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return Config::get('app.timezone');
    }

    /**
     * @param $string
     * @return mixed
     */
    public function clean_strings($string)
    {
        // trim leading spaces, period, or breaks
        $trimmed = trim($string, ' .');
        // make string lower case
        $lower = strtolower($trimmed);
        // replace any spaces with a das "-"
        $clean_commas = str_replace(',', ' ', $lower);
        // replace any spaces with a das "-"
        $clean = str_replace(' ', '-', $clean_commas);
        // return cleaned string
        return $clean;
    }
    /*
     * GLOBAL SCOPE START
     */
}