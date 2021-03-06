<?php

use MClassic\Datatables\Column\Column;
use MClassic\Datatables\Datatables;
use MClassic\Datatables\Engine\Modern;
use MClassic\Datatables\Query\Builder;

/**
 * Test search functionality for Datatables queries.
 */
class SearchTest extends PHPUnit_Framework_TestCase
{
    /** @var  Modern */
    protected $protocol;
    /** @var  array */
    protected $requestData;
    /** @var  array */
    protected $searchableData;

    public function setUp()
    {
        parent::setUp();
        $faker = Faker\Factory::create();
        $faker->seed(1974);
        $this->searchableData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $this->searchableData[] = [
                'name'        => $faker->name,
                'email'       => $faker->email,
                'description' => $faker->sentence,
                'active'      => $faker->boolean(66),
            ];
        }

        $this->requestData = [
            'draw'    => 123,
            'columns' => [],
        ];

        $columns = ['name', 'email', 'description', 'active'];
        foreach ($columns as $column) {
            $this->requestData['columns'][] = [
                'name'       => $column,
                'orderable'  => true,
                'searchable' => true,
            ];
        }

        $this->protocol = new Modern();
    }

    public function test_search_array_of_data()
    {
        $datatables = new Datatables($this->requestData);
        $datatables->setData($this->searchableData);
        $search = new Builder();

        /*
         * Build Columns
         * - Add them to Datatables object
         * - Create search object and add columns to that
         */
        foreach ($this->requestData['columns'] as $columnRequest) {
            $columnOptions = $columnRequest;
            $column = new Column($columnOptions);
            $search->pushColumn($column);
            $datatables->addColumn($column);
        }

        /*
         * Mimic a search. For this test, just pull a random index from a multi-dimensional array.
         */
        $randomIndex = mt_rand(1, 1000) - 1;
        $search->search(function(Builder $builder) use ($randomIndex) {
            $columns = $builder->getColumns();
            $find = [];
            foreach ($columns as $column) {
                $find[$column->getName()] = $this->searchableData[$randomIndex][$column->getName()];
            }

            return $find;
        });

        $results = $search->run();
        $columns = $search->getColumns();
        foreach ($columns as $column) {
            // $this->assertArrayHasKey($column->getName(), $this->searchableData[$randomIndex]);
            $this->assertEquals($this->searchableData[$randomIndex][$column->getName()], $results[$column->getName()]);
        }
    }

    public function test_search_query_string()
    {
        $query = 'bill johnson';
        $search = new Builder();
        $search = $search->query($query);

        $this->assertEquals($query, $search->getQuery());

        // We want to also make sure that the proper Builder instance gets passed into the callback by testing query here too
        $search->search(function(Builder $builder) use ($query) {
            $this->assertEquals($query, $builder->getQuery());
        });

        $search->run();
    }
}
