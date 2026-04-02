import { test, expect } from '@playwright/test';

const baseUrl = process.env.BASE_URL || 'http://localhost:8080';
const stripePublicKey = process.env.STRIPE_PUBLIC_KEY || '';
const stripeRestrictedPrivateKey = process.env.STRIPE_RESTRICTED_PRIVATE_KEY || '';

test('購入フロー', async ({ page }) => {
    // 管理画面に移動
    await page.goto(`${baseUrl}/admin/stripe_payment/config`);
    await page.fill('input[placeholder="ログインID"]', 'admin');
    await page.fill('input[placeholder="パスワード"]', 'password');
    await page.click('button:has-text("ログイン")');

    await expect(page).toHaveURL(`${baseUrl}/admin/stripe_payment/config`);

    await page.waitForSelector('button:has-text("入力欄を表示")');
    // 入力欄を表示ボタンをクリックして公開鍵フィールドを表示
    const buttons = await page.$$('button:has-text("入力欄を表示")');
    for (const button of buttons) {
        await button.click();
    }

    await page.waitForSelector('input#config_public_key');
    await page.waitForSelector('input#config_restricted_private_key');

    await page.fill('input#config_public_key', stripePublicKey);
    await page.fill('input#config_restricted_private_key', stripeRestrictedPrivateKey);
    await page.click('button:has-text("登録")');

    //「登録しました。」が表示されることを確認
    await expect(page.locator('text=登録しました。')).toBeVisible();

    // ログインページに移動
    await page.goto(`${baseUrl}/mypage/login`);

    // フォームに入力
    await page.fill('input[placeholder="メールアドレス"]', 'testuser@example.com');
    await page.fill('input[placeholder="パスワード"]', 'password123password123');

    // 次回自動ログインをチェック
    await page.check('text=次回から自動的にログインする');

    // ログインボタンをクリック
    await page.click('button:has-text("ログイン")');

    // ログイン成功を確認
    await expect(page).toHaveURL(`${baseUrl}/`);

    // チェリーアイスサンドを表示
    await page.goto(`${baseUrl}/products/detail/2`);

    await page.click('button:has-text("カートに入れる")');

    // await page.waitForSelector('a:has-text("カートへ進む")');
    await page.waitForSelector('.ec-modal .ec-inlineBtn--action:visible');
    // カートへ進むボタンをクリック（モーダル内限定）
    await page.click('.ec-modal .ec-inlineBtn--action');

    // レジに進むボタンをクリック
    await page.click('a:has-text("レジに進む")');

    // ショッピングページが表示されることを確認
    await expect(page).toHaveURL(`${baseUrl}/shopping`);

    // 「Stripe決済」のラジオボタンが選択されていることを確認
    const stripeRadioButton = await page.locator('div.ec-orderPayment input[type="radio"][checked]');
    await expect(stripeRadioButton).toBeVisible();
    
    // iframeが確実に DOM に現れるまで待機（表示されるまでではない）
    const stripeIframeHandle = await page.waitForSelector('iframe[title="セキュアな支払い入力フレーム"]', {
        timeout: 30000,
        state: 'attached'  // visible ではなく attached を使うとDOMに出現するまでを待つ
    });

    const stripeIframe = await stripeIframeHandle.contentFrame();
    if (!stripeIframe) {
        throw new Error('Stripe iframe の読み込みに失敗しました');
    }

    const cardNumberField = stripeIframe.locator('input[name="number"]');
    await cardNumberField.fill('4242424242424242');

    // 有効期限とCVCを入力
    await stripeIframe.locator('input[name="expiry"]').fill('1234');
    await stripeIframe.locator('input[name="cvc"]').fill('123');
    
    // iframe内で国ドロップダウンを「日本（JP）」に選択
    await stripeIframe.locator('select[name="country"]').selectOption('JP');

    await page.click('button:has-text("確認する")');

    await page.waitForTimeout(5000); // 5秒ウェイト追加

    // 確認画面が表示されることを確認
    await expect(page).toHaveURL(`${baseUrl}/shopping/confirm`);

    // await page.screenshot({ path: 'screenshots/fullpage.png', fullPage: true });

    const paymentSection = page.locator('div.ec-orderPayment:has(h2:has-text("お支払方法"))');
    await expect(paymentSection).toContainText('Stripe決済(￥0)');
    await expect(paymentSection).toContainText('支払詳細：card (xxxx-xxxx-xxxx-4242)');

    await page.click('button:has-text("注文する")');

    await expect(page).toHaveURL(`${baseUrl}/shopping/complete`);
});