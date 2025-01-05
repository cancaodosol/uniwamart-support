<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use App\Models\ShopifyProduct;
use App\Models\AccountingGroup;

/** 
 * create command
 * - sail artisan make:test ShopifyProductImportTest
 * execute command
 * - sail phpunit tests/Feature/ShopifyProductImportTest.php
 */
class ShopifyProductImportTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        \Log::debug("start: test_example");
        // $csvFile = ".".Storage::url('app/csv/products_export_test.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        // 重複した商品コードの取得
        $duplicateProductCodes = $this->get_duplicate_code("ProductCode");

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        // スマレジに登録させたくない商品名一覧を取得
        $exclutionTitles = $this->getExclutionTitles();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;
            if($product->isEmpty()) continue;
            $message = "";
            if(strlen($product->getProductCode()) > 20) continue; // MEMO: 商品コードは20文字以下の制限があるため、その調査用。
            if(in_array($product->getProductCode(), $duplicateProductCodes)) continue; // MEMO: 商品コードが重複しているものは除去。
            if(in_array($product->title, $exclutionTitles)) continue; // MEMO: スマレジ取り込み対象外のため、除外。
            $ifnullProductCode = sprintf('UNIMA%08d', 1000000 + $count);
            $line .= $product->toSmaregiFormart(1000000 + $count, $ifnullProductCode).",".$product->getGroupName($groups)."\n";
        }
        \Log::debug($line);
        fclose($file_handle);
    }

    /**
     * A basic feature test example.
     */
    public function test_import_error(): void
    {
        \Log::debug("start: test_import_error");
        // $csvFile = ".".Storage::url('app/csv/products_export_test.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        // 重複したコードの取得
        $duplicateHandleCodes = $this->get_duplicate_code("Handle");
        $duplicateProductCodes = $this->get_duplicate_code("ProductCode");
        $duplicateSkus = $this->get_duplicate_code("Sku");
        $duplicateBarcodes = $this->get_duplicate_code("Barcode");

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;
            if($product->isEmpty()) continue;
            $message = "";
            if(in_array($product->handle, $duplicateHandleCodes)) $message .= "×ハンドルが重複している";
            if(in_array($product->sku, $duplicateSkus)) $message .= "×SKUが重複している";
            if(in_array($product->barcode, $duplicateBarcodes)) $message .= "×バーコードが重複している";
            if(strlen($product->getProductCode()) > 20) $message .= "×商品コードが20文字より大きい。";
            if(in_array($product->getProductCode(), $duplicateProductCodes)) $message .= "×商品コードが重複している";

            $groupName = $product->getGroupName($groups);
            if($groupName == "") $message .= "×経理用分類が存在しない";

            $line .= $product->toString().",".$groupName.",".$message."\n";
        }
        \Log::debug($line);
        fclose($file_handle);
    }

    /**
     * A basic feature test example.
     */
    private function get_duplicate_code($targetColumnName = "ProductCode"): array
    {
        \Log::debug("start: test_duplicate_product_code");
        // $csvFile = ".".Storage::url('app/csv/products_export_test.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $targetColumnValues = [];
        $duplicateTargetColumnValues = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            if($product->isEmpty()) continue;
            $targetColumnValue = $targetColumnName == "ProductCode" ? $product->getProductCode() : 
                ($targetColumnName == "Barcode" ? $product->barcode : 
                ($targetColumnName == "Sku" ? $product->sku : 
                ($targetColumnName == "Handle" ? $product->handle : "")));
            if($targetColumnValue == "") continue;
            if(in_array($targetColumnValue, $targetColumnValues)){
                if(in_array($targetColumnValue, $duplicateTargetColumnValues)) continue;
                $duplicateTargetColumnValues[] = $targetColumnValue;
                $line .= $targetColumnValue."\n";
                continue;
            }
            $targetColumnValues[] = $targetColumnValue;
        }
        fclose($file_handle);

        return $duplicateTargetColumnValues;
    }

    private function getExclutionTitles(){
        return [
            'テストページ',
            'テスト：迫くんセット',
            '施設設備＜D＞',
            '施設設備＜C＞',
            '施設設備＜B＞',
            '施設設備＜A＞',
            'ドラえもん鉄玉子',
            '禊 修理費用',
            '正藍染め 苧麻のふんどし/大和ふんてぃ',
            'まかないチケット510円',
            'まかないチケット800円',
            'まかないチケット1000円',
            '食券510円',
            '食券800円',
            '食券1000円',
            'コーヒー定期便用　賀茂ブレンド変更ページ',
            '復元ドライヤーPro　コピー',
            '北口本宮富士浅間神社セミナー＜会員価格＞',
            'べじわの稲荷',
            '宇佐神宮参拝セミナー＜会員価格＞',
            '大神神社参拝セミナー2023＜会員価格＞',
            'センテン結界',
            '【トークbar】チーズセット',
            '【トークbar】クリームチーズクランベリーレーズンコンプレ',
            '【トークbar】マドレーヌ',
            '【トークbar】フィナンシェ',
            '【トークbar】白ワインカクテル',
            '【トークbar】赤ワインカクテル',
            '【トークbar】アルリアワイン 赤',
            '【トークbar】トリニティワイン 白',
            '【トークbar】トリニティワイン 赤',
            '石上神社参拝セミナー＜会員価格＞',
            '【卸販売】メキシコ産コーヒー 1kg　オンライン発送',
            '店頭→物流在庫移動メモ',
            'ヤマト発送伝票',
            '【花えりおススメセット】エバメール、ミントフェイス、fuuトナー',
            '香水の木箱',
            '【相原おススメセット】だし栄養スープ、くろご（小）',
            'アタッシュケース',
            'ドリップバック5個セット用OPP（単位：1袋）',
            'サウンドシステム　ミラキュルーズ',
            '＜ゆにわの神棚＞住神 -SUMIKA-（三社）',
            '＜ゆにわの神棚＞ 住神 -SUMIKA-（一社）',
            'Gift Receipt',
            'Gift Message',
            'メッセージカード',
            'プチプチビン小',
            'プチプチビン大',
            '化粧水テスト2',
            '化粧水テスト（2個セット）',
            '伊勢六芒星水晶',
            '水晶クラスター（石勉強会用）',
            'パンセット',
            '2019＆2020 アルリアワイン飲み比べ4本セット',
            '最澄ブレンドセルフサービス',
            '復元ドライヤーmini　',
            '【白金発送】食品・珈琲',
            'ゆにわレター',
            '【東京衣類販売用】映画『美味しいごはん』ロゴ入りTシャツ',
            '味鍋なごみ(小)',
            '大麻アクセサリー　叶 三五七(KANAI/san go shichi) E',
            '神社秘伝DVD 2020 西宮神社参拝セミナー',
            '白金フィナンシェ',
            '白金マドレーヌ',
            'やきそば パン',
            '在庫テスト',
            '京かえる[歯ぐきも] 歯みがき　35g',
            'ナン（バター）',
            'ひじき煮パン',
            'フレンチトースト',
            '思いつきパン',
            'さつまいもミルクあんパン',
            '黒ごまあんぱん',
            'グラタンドフィノアパン',
            'ごぼうとにんじんの金平パン',
            'ベーコン&玉ねぎ',
            'むにゅたまパン',
            'さつまいもとあんこのコンプレ',
            '代引き3万円以下',
            '代引き1万円以下',
            '八重岳 レーズンココナッツクッキー',
            '八重岳 キャロブクッキー',
            '八重岳 ごまクッキー',
            '八重岳 しょうがクッキー',
            '＜農家直送便＞ゆにわのブランド米『美味しいごはん』5kg',
            'ゆにわのグラノーラ ココナッツ 250g',
            'ゆにわの グラノーラ カカオ 250g',
            'れんこん粉顆粒 1.5g✕30パック',
            '鼻うがいセット(ボウル・真生塩・キパワーソルト［袋］)',
            'Swattoko（すわっとこ）',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ5月号',
            'ゆにわ塾 2016年ﾊﾞｯｸﾅﾝﾊﾞｰ4月号',
            'ｾﾝﾃﾝCLUB 2016年ﾊﾞｯｸﾅﾝﾊﾞｰ1月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ12月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ11月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ10月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ9月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ8月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ7月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ6月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ4月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ3月号',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ2月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ12月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ11月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ10月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ9月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ8月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ7月号',
            '［CD］センテンCLUB 2014年6月号 橿原神宮',
            '［CD］センテンCLUB 2014年5月号 大豊神社',
            'ｾﾝﾃﾝCLUB 2015年ﾊﾞｯｸﾅﾝﾊﾞｰ1月号',
            'ｾﾝﾃﾝCLUB 2014年ﾊﾞｯｸﾅﾝﾊﾞｰ4月号',
            '［CD・DVD］ゆにわ塾2019年10月号 ゴッドハンドの秘密',
            '映画『美味しいごはん』パンフレット',
            '［CD・DVD］ゆにわ塾2018年12月号〜龍神を動かす方法〜',
            '［CD・DVD］ゆにわ塾2018年11月号〜豊かになるために〜',
            '［CD・DVD］ゆにわ塾 2016年11月号 霊界の構造改革〜新時代のルールを知る〜',
            '［CD・DVD］ゆにわ塾 2016年10月号 精神と肉体 2つの視点から健康を語る',
            '［DVD］ゆにわ塾 2016年9月号 感覚を高めるために大事なこと',
            '［CD・DVD］ゆにわ塾 2016年8月号 日本・世界を変えていくために',
            '［CD・DVD］ゆにわ塾 2016年7月号 求道者としてのあり方',
            '［CD・DVD］ゆにわ塾 2016年6月号 天のエネルギーをいただく',
            '［CD・DVD］ゆにわ塾 2016年5月号 人と体のエネルギーを高める',
            '［CD・DVD］ゆにわ塾 2017年8月号 人生が物語になる生き方',
            '［CD・DVD］ゆにわ塾 2017年7月号 天命は関係性の中にある',
            '［CD・DVD］ゆにわ塾 2017年4月号 FLOWの法則',
            '［CD・DVD］ゆにわ塾 2017年3月号 魂の岩戸が開く新しい時代の幕開け',
            '［CD・DVD］ゆにわ塾 2017年1月号 今年の開運テーマ 〜まつりの話〜',
            '［CD・DVD］塾　2017年2月号 下座の行で魂の本音を知る',
            '［CD・DVD］ゆにわ塾 2016年2月号 神がかりと憑依',
            '［CD・DVD］ゆにわ塾 2016年3月号 陰陽統合の道',
            '［CD・DVD］ゆにわ塾 2016年4月号 自分を鍛える道',
            '［DVD］センテンCLUB 2015年4月号 ココロを育てる',
            '［DVD］センテンCLUB 2015年2月号 2015年の展望と開運',
            '［DVD］センテンCLUB 2015年12月号 みろくの世の真実 これからどういう時代が来るのか？',
            '［DVD］センテンCLUB 2016年1月号 末永く幸せがつづくスズカの道',
            '［DVD］センテンCLUB 2015年9月号 リアルと合理性をつきつめる',
            '［DVD］センテンCLUB 2015年7月号 志の立て方',
            '［DVD］センテンCLUB 2015年11月号 心のステージを高める 太陽の道',
            '［DVD］センテンCLUB 2014年8月号 開運秘伝！強いココロをつくる〜メンタル力UPの秘訣〜',
            '［DVD］センテンCLUB 2015年3月号 未来を創るために シリウス時代の到来',
            '［CD］センテンCLUB 2014年9月号 イノベーションが求められる今、私たちは何をすべきか？',
            '［CD］センテンCLUB 2014年11月 旧暦七夕セミナー',
            '［DVD］センテンCLUB 2014年12月号 ゆるむことの大切さ',
            '［DVD］センテンCLUB 2015年1月号 気持ちのいい方へ逃げない',
            '［DVD］センテンCLUB 2015年6月号 幸運を呼び込む循環の法則',
            '［DVD］センテンCLUB 2015年7月号 魂の本音を知る',
            '［DVD］センテンCLUB 2015年8月号 "たましい"の炎を灯す',
            '［DVD］センテンCLUB 2015年9月号 理想と現実を統合する',
            '月の秘法セミナー特別ＤＶＤ教材',
            '［CD］センテンCLUB 2014年6月号 橿原神宮',
            '［CD］センテンCLUB 2014年5月号 大豊神社',
            '［CD］センテンCLUB 2014年3月号 春日大社',
            '［CD］センテンCLUB 2014年2月号 井草八幡宮',
            '［CD］センテンCLUB 2014年1月号 多賀大社',
            '神社秘伝DVD 2013 西宮神社参拝セミナー',
            '麻紐',
            '土粒状散布剤 環境改善用Ｘ「底力」',
            '天空の高級毛布',
            'エンバランス　水タンク12Ｌ コックセット中ゴム',
            'エンバランス　水タンク12Ｌ コックセット',
            'マカイバリ茶園 有機JASダージリン秋摘み紅茶100g ヴィンテージ',
        ];
    }
}
