<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use App\Models\AccountingGroup;

/** 
 * create command
 * - sail artisan make:test AccountingGroupImportTest
 * execute command
 * - sail phpunit tests/Feature/AccountingGroupImportTest.php
 */
class AccountingGroupImportTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        \Log::debug("start: test_example");
        $csvFile = ".".Storage::url('app/csv/products_accounting_group.csv');

        $accountingGroups = [];
        $duplicateTitles = AccountingGroup::getDuplicateCode($csvFile, "title");

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $group = AccountingGroup::loadCsvRow($csvRow);
            if(in_array($group->productTitle, $duplicateTitles)) continue;
            $accountingGroups[] = $group;
            $line .= $group->toString()."\n";
        }
        // \Log::debug($line);
        fclose($file_handle);
    }

    /**
     * A basic feature test example.
     */
    public function test_equals(): void
    {
        \Log::debug("start: test_equals");

        $g1 = new AccountingGroup();
        $g1->productTitle = "中国線香　避瘟";
        $this->assertEquals(true, $g1->equals("中国線香 避瘟"));

        $g2 = new AccountingGroup();
        $g2->productTitle = "中国線香 避瘟";
        $this->assertEquals(true, $g2->equals("中国線香　避瘟"));

        $g3 = new AccountingGroup();
        $g3->productTitle = "十種神宝の香り(30ml)  ";
        $this->assertEquals(true, $g3->equals("十種神宝の香り(30ml)"));

        $g4 = new AccountingGroup();
        $g4->productTitle = "十種神宝の香り(30ml)　";
        $this->assertEquals(true, $g4->equals("十種神宝の香り(30ml)"));

        $g5 = new AccountingGroup();
        $g5->productTitle = "【ドリップバッグ】三嶋ブレンド５個セット";
        $this->assertEquals(true, $g5->equals("【ドリップバッグ】三嶋ブレンド5個セット"));

        $g6 = new AccountingGroup();
        $g6->productTitle = "【fuu. 】薬膳香草茶 ナツメローズヒップ 100g";
        $this->assertEquals(true, $g6->equals("【fuu. 】薬膳香草茶 ナツメローズヒップ 100ｇ"));

        // $g7 = new AccountingGroup();
        // $g7->productTitle = "出張 よもぎあんパン";
        // $this->assertEquals(true, $g7->equals("出張　よもぎあんぱん"));

        $g8 = new AccountingGroup();
        $g8->productTitle = "【［お歳暮］甲斐國一宮ワイン3種類セット】";
        $this->assertEquals(true, $g8->equals("【［お歳暮］甲斐國一宮ワイン３種類セット】"));

        // $g9 = new AccountingGroup();
        // $g9->productTitle = "ミヨシ 無添加洗濯用液体せっけん ボトル1.1L";
        // $this->assertEquals(true, $g9->equals("ﾐﾖｼ 無添加洗濯用液体せっけん ﾎﾞﾄﾙ1.1L"));

        $g10 = new AccountingGroup();
        $g10->productTitle = "ミヨシ 無添加洗濯用液体せっけん 詰替え用 1000ml";
        $this->assertEquals(true, $g10->equals("ﾐﾖｼ　無添加洗濯用液体せっけん 詰替え用 1000ml"));
    }
}
