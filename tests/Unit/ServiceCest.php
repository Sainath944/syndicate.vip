<?php

namespace Tests\Unit;

use \Approach\Service\Service;
use Tests\Support\UnitTester;
use \Approach\Service\Target;
use \Approach\Service\Format;
use \Approach\Service\CSV;

use \Approach\Scope;
use \Approach\path;

class ServiceCest
{
    private $scope;
    private $example_file_path_json;
    private $example_file_path;
    private $example_file_path_csv;
    private $example_json_contents;
    private $example_csv_contents;
    private $example_output_file_path_json;
    private $example_output_file_path_csv;

    public function _before(UnitTester $I)
    {
        $path_to_project = __DIR__ . 'C:/Users/gudde/syndicate.vip/support/lib/service/tests';
        $path_to_approach = __DIR__ . 'C:/Users/gudde/syndicate.vip/support/lib/approach';
        $path_to_support = __DIR__ . 'C:/Users/gudde/syndicate.vip/tests/Support';

        $this->scope = new Scope(
            path: [
                path::project->value        =>  $path_to_project,
                path::installed->value      =>  $path_to_approach,
                path::support->value        =>  $path_to_support,
            ],
        );

        $this->example_file_path = Scope::GetPath(path::support) . 'service/tests/example';
        $this->example_file_path_json = $this->example_file_path . '.json';
        $this->example_file_path_csv = $this->example_file_path . '.csv';

        $this->example_json_contents = file_get_contents($this->example_file_path_json);
        $this->example_csv_contents = file_get_contents($this->example_file_path_csv);

        $this->example_output_file_path_json = Scope::GetPath(path::support) . 'service/tests/example_output.json';
        $this->example_output_file_path_csv = Scope::GetPath(path::support) . 'service/tests/example_output.csv';

        if (file_exists($this->example_output_file_path_json)) {
            unlink($this->example_output_file_path_json);
        }
        if (file_exists($this->example_output_file_path_csv)) {
            unlink($this->example_output_file_path_csv);
        }
    }

    public function _after(UnitTester $I)
    {
        if (file_exists($this->example_output_file_path_json)) {
            unlink($this->example_output_file_path_json);
        }
        if (file_exists($this->example_output_file_path_csv)) {
            unlink($this->example_output_file_path_csv);
        }
    }

    public function transformJsonToCsvAndBack(UnitTester $I)
    {
        CSV::register();

        // Step 1: JSON to CSV
        $serviceToCsv = new Service(
            auto_dispatch: false,
            target_in: target::file,
            target_out: target::file,
            format_in: format::json,
            format_out: format::csv,
            input: $this->example_file_path_json,
            output: [$this->example_output_file_path_csv],
        );

        $serviceToCsv->dispatch();

        $I->assertFileExists($this->example_output_file_path_csv);
        $csvContents = file_get_contents($this->example_output_file_path_csv);
        $I->assertNotEmpty($csvContents);

        // Step 2: CSV back to JSON
        $serviceToJson = new Service(
            auto_dispatch: false,
            target_in: target::file,
            target_out: target::file,
            format_in: format::csv,
            format_out: format::json,
            input: $this->example_output_file_path_csv,
            output: [$this->example_output_file_path_json],
        );

        $serviceToJson->dispatch();

        $I->assertFileExists($this->example_output_file_path_json);
        $jsonContents = file_get_contents($this->example_output_file_path_json);
        $I->assertJson($jsonContents);
        $I->assertStringContainsString($this->example_json_contents, $jsonContents);
    }

    public function handleInvalidJsonInput(UnitTester $I)
    {
        $invalidJsonPath = Scope::GetPath(path::support) . 'service/tests/invalid_example.json';
        file_put_contents($invalidJsonPath, '{invalid_json_content}');

        $service = new Service(
            auto_dispatch: false,
            target_in: target::file,
            format_in: format::json,
            input: $invalidJsonPath
        );

        try {
            $service->dispatch();
            $I->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $I->assertStringContainsString('Invalid JSON', $e->getMessage());
        } finally {
            if (file_exists($invalidJsonPath)) {
                unlink($invalidJsonPath);
            }
        }
    }

    public function transformCsvToJsonAndBack(UnitTester $I)
    {
        CSV::register();

        // Step 1: CSV to JSON
        $serviceToJson = new Service(
            auto_dispatch: false,
            target_in: target::file,
            target_out: target::file,
            format_in: format::csv,
            format_out: format::json,
            input: $this->example_file_path_csv,
            output: [$this->example_output_file_path_json],
        );

        $serviceToJson->dispatch();

        $I->assertFileExists($this->example_output_file_path_json);
        $jsonContents = file_get_contents($this->example_output_file_path_json);
        $I->assertJson($jsonContents);

        // Step 2: JSON back to CSV
        $serviceToCsv = new Service(
            auto_dispatch: false,
            target_in: target::file,
            target_out: target::file,
            format_in: format::json,
            format_out: format::csv,
            input: $this->example_output_file_path_json,
            output: [$this->example_output_file_path_csv],
        );

        $serviceToCsv->dispatch();

        $I->assertFileExists($this->example_output_file_path_csv);
        $csvContents = file_get_contents($this->example_output_file_path_csv);
        $I->assertNotEmpty($csvContents);
        $I->assertStringContainsString($this->example_csv_contents, $csvContents);
    }
    public function handleNullJsonValues(UnitTester $I)
    {
        $nullJsonPath = Scope::GetPath(path::support) . 'service/tests/null_example.json';
        file_put_contents($nullJsonPath, json_encode(['key' => null]));

        $serviceToCsv = new Service(
            auto_dispatch: false,
            target_in: target::file,
            format_in: format::json,
            format_out: format::csv,
            input: $nullJsonPath,
            output: [$this->example_output_file_path_csv],
        );

        $serviceToCsv->dispatch();

        $I->assertFileExists($this->example_output_file_path_csv);
        $csvContents = file_get_contents($this->example_output_file_path_csv);
        $I->assertNotEmpty($csvContents);

        if (file_exists($nullJsonPath)) {
            unlink($nullJsonPath);
        }
    }
    public function mergeJsonData(UnitTester $I) {
        $additionalDataPath = Scope::GetPath(path::support) . 'service/tests/additional_example.json';
        file_put_contents($additionalDataPath, json_encode([
            ['id' => 4, 'name' => 'Item 4', 'active' => true],
            ['id' => 5, 'name' => 'Item 5', 'active' => false]
        ]));

        $originalData = json_decode(file_get_contents($this->example_file_path_json), true);
        $additionalData = json_decode(file_get_contents($additionalDataPath), true);

        $mergedData = array_merge($originalData, $additionalData);

        $service = new Service(
            auto_dispatch: false,
            target_in: target::variable,
            target_out: target::variable,
            format_in: format::json,
            format_out: format::json,
            input: json_encode($mergedData),
        );

        $outputData = $service->dispatch()[0];

        $I->assertJson($outputData);

    }
    public function handleLargeJsonFile(UnitTester $I)
    {
        $largeJsonData = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeJsonData[] = ['id' => $i, 'name' => 'Item ' . $i, 'active' => $i % 2 === 0];
        }
        $largeJsonPath = Scope::GetPath(path::support) . 'service/tests/large_example.json';
        file_put_contents($largeJsonPath, json_encode($largeJsonData));

        $service = new Service(
            auto_dispatch: false,
            target_in: target::file,
            target_out: target::variable,
            format_in: format::json,
            format_out: format::json,
            input: $largeJsonPath,
        );

        $output = $service->dispatch()[0];

        $I->assertJson($output);
        $decodedData = json_decode($output, true);
        $I->assertCount(10000, $decodedData);
    }

}


