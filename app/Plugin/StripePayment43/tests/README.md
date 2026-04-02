# テスト実行方法（Playwright）

このプロジェクトでは [Playwright](https://playwright.dev/) を利用して E2E テストを行っています。

## 前提条件

-   Node.js および npm がインストールされていること
-   サーバー（EC-CUBE など）が `http://localhost:8080` で起動していること

## セットアップ

1. 依存パッケージのインストール

```bash
npm install
```

2. Playwright のブラウザバイナリをインストール

```bash
npx playwright install
```

## テストの実行

以下のコマンドでテストを実行できます。

```bash
npx playwright test
```

特定のテストファイルのみ実行したい場合は、ファイル名を指定します。

```bash
npx playwright test tests/login.spec.ts
```

## テスト内容

-   管理画面へのログイン
-   Stripe 公開鍵・秘密鍵の登録
-   フロントのログイン・商品購入・Stripe 決済フロー
-   購入完了までの一連の動作確認

## テストレポート

テスト実行後、`playwright-report/` ディレクトリに HTML レポートが生成されます。

レポートを表示するには:

```bashhttps://summitjapan.awslivestream.com/aws-10/aws-10
npx playwright show-report
```

---

ご不明点があれば `tests/login.spec.ts` をご参照ください。
