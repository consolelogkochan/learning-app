// ----- 学習ログ投稿フォームの処理（カスタムプルダウン対応版） -----

function initializeCustomSelect(wrapper) {
    const trigger = wrapper.querySelector('.custom-select-trigger');
    const options = wrapper.querySelector('.custom-options');
    const hiddenSelect = wrapper.querySelector('select');
    const triggerSpan = trigger.querySelector('span');

    // 編集画面用に、初期値を設定
    if (hiddenSelect.value) {
        const selectedOption = options.querySelector(`.custom-option[data-value="${hiddenSelect.value}"]`);
        if(selectedOption) {
            triggerSpan.textContent = selectedOption.textContent;
            options.querySelector('.selected')?.classList.remove('selected');
            selectedOption.classList.add('selected');
        }
    }

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelectorAll('.custom-select-wrapper.open').forEach(w => {
            if (w !== wrapper) w.classList.remove('open');
        });
        wrapper.classList.toggle('open');
    });

    options.addEventListener('click', function(e) {
        if (e.target.classList.contains('custom-option')) {
            const selectedValue = e.target.getAttribute('data-value');
            const selectedText = e.target.textContent;

            hiddenSelect.value = selectedValue;
            triggerSpan.textContent = selectedText;
            
            options.querySelector('.selected')?.classList.remove('selected');
            e.target.classList.add('selected');

            wrapper.classList.remove('open');
        }
    });
}

// ページ上のすべてのカスタムプルダウンを初期化
document.querySelectorAll('.custom-select-wrapper').forEach(initializeCustomSelect);

// 「＋ 学習項目を追加」ボタンの処理
const addItemBtn = document.getElementById('add-item-btn');
const itemsContainer = document.getElementById('learning-items-container');

if (addItemBtn && itemsContainer) {
    addItemBtn.addEventListener('click', function() {
        const firstItem = itemsContainer.querySelector('.learning-item');
        const newItem = firstItem.cloneNode(true);
        // 新しい項目の値をリセット
        newItem.querySelector('select').value = '';
        newItem.querySelector('input[type="number"]').value = '';
        newItem.querySelector('.custom-select-trigger span').textContent = 'カテゴリを選択';
        newItem.querySelector('.selected')?.classList.remove('selected');

        // ▼▼▼ ここから削除ボタンの作成と追加 ▼▼▼
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-item-btn'; // CSSでスタイルを当てるためのクラス
        removeBtn.textContent = '削除';
        newItem.querySelector('.duration-group').appendChild(removeBtn); // 新しい学習項目の末尾に削除ボタンを追加
        // ▲▲▲ ここまで追加 ▲▲▲
        
        itemsContainer.appendChild(newItem);
        // 新しく追加したプルダウンも初期化
        initializeCustomSelect(newItem.querySelector('.custom-select-wrapper'));
    });

    // 削除ボタンの処理
    itemsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-item-btn')) {
            e.target.closest('.learning-item').remove();
        }
    });
}

// ページ全体をクリックしたら、開いているプルダウンを閉じる
document.addEventListener('click', function() {
    document.querySelectorAll('.custom-select-wrapper.open').forEach(w => {
        w.classList.remove('open');
    });
});

/**
 * HTML文字列をエスケープするためのヘルパー関数
 */
function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}


// ----- グラフ描画処理 -----

// グラフを描画するcanvas要素を取得
const chartCanvas = document.getElementById('learningTimeChart');

// canvas要素が存在するページ（dashboard.php）でのみ、グラフ描画処理を実行
if (chartCanvas) {
    // data-* 属性からJSON文字列を取得し、JavaScriptの配列に変換
    const labels = JSON.parse(chartCanvas.dataset.labels);
    const data = JSON.parse(chartCanvas.dataset.data);

    // Chart.jsで棒グラフを生成
    new Chart(chartCanvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '学習時間 (分)',
                data: data,
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// ----- 円グラフ描画処理 -----

// 円グラフを描画するcanvas要素を取得
const pieChartCanvas = document.getElementById('categoryPieChart');

if (pieChartCanvas) {
    // data-* 属性からデータを取得
    const labels = JSON.parse(pieChartCanvas.dataset.labels);
    const data = JSON.parse(pieChartCanvas.dataset.data);

    new Chart(pieChartCanvas, {
        type: 'pie', // グラフの種類を 'pie' に
        data: {
            labels: labels, // 凡例 (カテゴリ名)
            datasets: [{
                label: '学習時間 (分)',
                data: data,
                // 各カテゴリの色を動的に生成（任意）
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
            }]
        },
        options: {
            responsive: true, // レスポンシブ対応
            maintainAspectRatio: false,
        }
    });
}

// ===== メニュー開閉ロジック (最終・シンプル版) =====

document.addEventListener('click', function(e) {
    const toggleButton = e.target.closest('.menu-toggle-btn, .profile-menu-toggle');

    // --- 1. メニュー外クリックの判定 ---
    // クリックされたのがメニューコンテナ（ボタンとメニュー全体を囲むdiv）の外側だった場合
    if (!e.target.closest('.menu-container, .profile-menu-container')) {
        // 開いているメニューをすべて閉じる
        document.querySelectorAll('.log-menu.active, .profile-menu.active').forEach(function(menu) {
            menu.classList.remove('active');
        });
    }

    // --- 2. メニューボタンクリックの判定 ---
    if (toggleButton) {
        // 対応するメニューを取得
        const menu = toggleButton.nextElementSibling;
        
        // もしそのメニューがすでに開いていたら、閉じる
        if (menu.classList.contains('active')) {
            menu.classList.remove('active');
        } else {
            // そうでなければ、一度すべてのメニューを閉じてから、そのメニューだけを開く
            document.querySelectorAll('.log-menu.active, .profile-menu.active').forEach(function(openMenu) {
                openMenu.classList.remove('active');
            });
            menu.classList.add('active');
        }
    }
});


// ===== コメントの「もっと読む」機能 (独立版) =====
const logList = document.querySelector('.log-list');
if (logList) {
    logList.addEventListener('click', function(e) {
        if (e.target.classList.contains('toggle-comments-btn')) {
            const button = e.target;
            const wrapper = button.previousElementSibling;
            const hiddenComments = wrapper.querySelectorAll('.comment[style*="display: none"]');

            if (button.textContent.includes('もっと読む')) {
                hiddenComments.forEach(comment => comment.style.display = 'block');
                button.textContent = '閉じる';
            } else {
                const allComments = wrapper.querySelectorAll('.comment');
                allComments.forEach((comment, index) => {
                    if (index >= 3) {
                        comment.style.display = 'none';
                    }
                });
                const remaining = allComments.length - 3;
                button.textContent = `もっと読む (残り${remaining}件)`;
            }
        }
    });
}

// ----- パスワード表示/非表示トグルの共通機能 -----
const showPasswordCheck = document.getElementById('show-password-check');

// この機能に必要なチェックボックスが存在するページでのみ実行
if (showPasswordCheck) {
    showPasswordCheck.addEventListener('change', function() {
        // idが"password"または"confirm_password"の入力欄をすべて取得
        const passwordFields = document.querySelectorAll('#password, #confirm_password');
        
        // チェックボックスの状態に応じて、type属性を一括で切り替える
        const type = this.checked ? 'text' : 'password';
        passwordFields.forEach(field => field.type = type);
    });
}

// ----- プロフィール画像プレビュー機能 -----
const imageInput = document.getElementById('profile_image');
const imagePreview = document.getElementById('image-preview');

// 画像プレビュー機能が必要なページ（profile_edit.php）でのみ実行
if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            // タグが置き換わる可能性があるので、プレビュー要素を再取得
            let currentPreview = document.getElementById('image-preview'); 
            
            // プレビュー要素がアイコン（Iタグ）だった場合
            if (currentPreview.tagName === 'I') {
                const newImg = document.createElement('img');
                newImg.id = 'image-preview';
                newImg.className = 'profile-avatar-large';
                currentPreview.parentNode.replaceChild(newImg, currentPreview);
                newImg.src = event.target.result;
            } else {
                // プレビュー要素が既にimgタグなら、srcを更新
                currentPreview.src = event.target.result;
            }
        }
        reader.readAsDataURL(file);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // save-stateクラスを持つ全ての要素を取得
    const saveStateTriggers = document.querySelectorAll('.save-state');
    
    saveStateTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(event) {
            // 本来の動作（ページ遷移やフォーム送信）を一旦停止
            event.preventDefault();

            // 保存したいフォームを特定
            // 今回は学習ログフォームのデータを保存する
            const formToSave = document.getElementById('log-form');
            if (!formToSave) return;

            // FormDataオブジェクトを使ってフォームの全データを取得
            const formData = new FormData(formToSave);

            // fetch APIを使って、裏側でsave_form_state.phpにデータを送信
            fetch('save_form_state.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // セッションへの保存が成功したら、本来の動作を実行
                    
                    // クリックされたのがsubmitボタンか？
                    if (trigger.type === 'submit') {
                        // 対応するフォームを送信
                        trigger.closest('form').submit();
                    } else if (trigger.tagName === 'A') {
                        // aタグの場合は、そのリンク先に遷移
                        window.location.href = trigger.href;
                    }
                } else {
                    // エラーの場合は、とりあえず本来の動作をそのまま実行
                    console.error('Failed to save form state:', data.message);
                    if (trigger.type === 'submit') {
                        trigger.closest('form').submit();
                    } else if (trigger.tagName === 'A') {
                        window.location.href = trigger.href;
                    }
                }
            })
            .catch(error => {
                console.error('Error during fetch:', error);
                // 通信失敗時も、本来の動作を実行
                if (trigger.type === 'submit') {
                    trigger.closest('form').submit();
                } else if (trigger.tagName === 'A') {
                    window.location.href = trigger.href;
                }
            });
        });
    });
});