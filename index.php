<?php
// JSONファイルを読み込む
$structured_file = 'arknights_structured_values.json';
$characters_file = 'arknights_characters.json';

$structured_data = [];
$characters_data = [];

// ファイルの存在確認と読み込み
if (file_exists($structured_file)) {
    $json = file_get_contents($structured_file);
    $structured_data = json_decode($json, true);
} else {
    echo "Error: $structured_file が見つかりません。";
    exit;
}

if (file_exists($characters_file)) {
    $json = file_get_contents($characters_file);
    $characters_data = json_decode($json, true);
} else {
    echo "Error: $characters_file が見つかりません。";
    exit;
}

// 職分(Branch)の全リストを作成（職業ごとの階層構造をフラットにする）
$all_branches = [];
if (isset($structured_data['profession_branches'])) {
    foreach ($structured_data['profession_branches'] as $prof => $branches) {
        foreach ($branches as $branch) {
            $all_branches[] = $branch;
        }
    }
    sort($all_branches); // 五十音順ソート
    $all_branches = array_unique($all_branches);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アークナイツ キャラクター検索</title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-main: #e0e0e0;
            --text-sub: #a0a0a0;
            --accent: #29b6f6;
            --accent-hover: #039be5;
            --border: #444;
            --active-bg: #29b6f6;
            --active-text: #000;
        }

        body {
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
        }

        h1, h3 { margin-top: 0; }

        /* 検索エリアのスタイル */
        .filter-container {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .filter-group {
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }
        
        .filter-group:last-child {
            border-bottom: none;
        }

        .filter-label {
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
            color: var(--accent);
        }

        .btn-filter {
            background-color: transparent;
            border: 1px solid var(--text-sub);
            color: var(--text-sub);
            padding: 6px 12px;
            margin: 0 5px 5px 0;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-filter:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* 選択状態のスタイル */
        .btn-filter.active {
            background-color: var(--active-bg);
            color: var(--active-text);
            border-color: var(--active-bg);
            font-weight: bold;
        }

        .status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .btn-reset {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* 結果表示エリア（グリッド） */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        /* キャラカードのスタイル */
        .char-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .char-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
        }

        .char-img-box {
            width: 100px;
            background-color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .char-img-box img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .char-info {
            padding: 10px 15px;
            flex: 1;
        }

        .char-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-main);
        }

        .char-tags {
            font-size: 0.8rem;
            color: var(--text-sub);
            margin-bottom: 8px;
        }

        .tag-badge {
            display: inline-block;
            background-color: #444;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 3px;
            margin-bottom: 3px;
            font-size: 0.75rem;
        }

        .char-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            font-size: 0.85rem;
            gap: 5px;
            color: #ccc;
        }

        .stat-row span:first-child {
            color: #888;
        }
    </style>
</head>
<body>

    <h1>アークナイツ キャラクター検索</h1>

    <!-- 検索フィルタエリア -->
    <div class="filter-container">
        
        <!-- 職業 -->
        <div class="filter-group">
            <span class="filter-label">職業</span>
            <div id="filter-profession">
                <?php
                if (isset($structured_data['profession_branches'])) {
                    foreach ($structured_data['profession_branches'] as $prof => $branches) {
                        echo "<button class='btn-filter' data-type='profession' data-value='" . htmlspecialchars($prof) . "'>" . htmlspecialchars($prof) . "</button>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- 職分 -->
        <div class="filter-group">
            <span class="filter-label">職分</span>
            <div id="filter-branch">
                <?php
                foreach ($all_branches as $branch) {
                    echo "<button class='btn-filter' data-type='branch' data-value='" . htmlspecialchars($branch) . "'>" . htmlspecialchars($branch) . "</button>";
                }
                ?>
            </div>
        </div>

        <!-- ブロック数 -->
        <div class="filter-group">
            <span class="filter-label">ブロック数</span>
            <div id="filter-block">
                <?php
                if (isset($structured_data['block_counts'])) {
                    foreach ($structured_data['block_counts'] as $block) {
                        echo "<button class='btn-filter' data-type='block_count' data-value='" . htmlspecialchars($block) . "'>" . htmlspecialchars($block) . "</button>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- 入手方法 -->
        <div class="filter-group">
            <span class="filter-label">入手方法</span>
            <div id="filter-obtain">
                <?php
                if (isset($structured_data['obtain_methods'])) {
                    foreach ($structured_data['obtain_methods'] as $method) {
                        echo "<button class='btn-filter' data-type='obtain_method' data-value='" . htmlspecialchars($method) . "'>" . htmlspecialchars($method) . "</button>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- 募集タグ -->
        <div class="filter-group">
            <span class="filter-label">募集タグ (AND検索)</span>
            <div id="filter-tags">
                <?php
                if (isset($structured_data['tags'])) {
                    foreach ($structured_data['tags'] as $tag) {
                        echo "<button class='btn-filter' data-type='tags' data-value='" . htmlspecialchars($tag) . "'>" . htmlspecialchars($tag) . "</button>";
                    }
                }
                ?>
            </div>
        </div>

    </div>

    <!-- ステータスバー -->
    <div class="status-bar">
        <span id="result-count" style="font-weight:bold; font-size:1.1rem;">検索結果: 全件表示中</span>
        <button class="btn-reset" onclick="resetFilters()">条件をリセット</button>
    </div>

    <!-- 結果表示エリア -->
    <div id="results" class="results-grid">
        <!-- JSでここにカードを生成 -->
    </div>

    <script>
        // PHPからデータをJS変数に渡す
        const allCharacters = <?php echo json_encode($characters_data); ?>;
        
        // 現在のフィルタ状態
        let activeFilters = {
            profession: [],
            branch: [],
            block_count: [],
            obtain_method: [],
            tags: []
        };

        // DOM読み込み完了時
        document.addEventListener('DOMContentLoaded', () => {
            renderCharacters(allCharacters);
            setupEventListeners();
        });

        // イベントリスナーの設定
        function setupEventListeners() {
            const buttons = document.querySelectorAll('.btn-filter');
            buttons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const type = e.target.dataset.type;
                    const value = e.target.dataset.value;
                    
                    toggleFilter(type, value, e.target);
                });
            });
        }

        // フィルタの切り替え処理
        function toggleFilter(type, value, element) {
            const index = activeFilters[type].indexOf(value);
            
            if (index === -1) {
                // 追加
                activeFilters[type].push(value);
                element.classList.add('active');
            } else {
                // 削除
                activeFilters[type].splice(index, 1);
                element.classList.remove('active');
            }

            // 検索実行
            filterAndRender();
        }

        // 検索ロジックと描画呼び出し
        function filterAndRender() {
            const filtered = allCharacters.filter(char => {
                // 1. 職業 (OR検索: 選択されたもののどれか一つに一致、未選択なら通過)
                if (activeFilters.profession.length > 0) {
                    if (!activeFilters.profession.includes(char.profession)) return false;
                }

                // 2. 職分 (OR検索)
                if (activeFilters.branch.length > 0) {
                    if (!activeFilters.branch.includes(char.branch)) return false;
                }

                // 3. ブロック数 (OR検索)
                if (activeFilters.block_count.length > 0) {
                    if (!activeFilters.block_count.includes(char.block_count)) return false;
                }

                // 4. 入手方法 (OR検索)
                if (activeFilters.obtain_method.length > 0) {
                    // 入手方法は部分一致か完全一致か微妙だが、データ構造上は完全一致で比較
                    if (!activeFilters.obtain_method.includes(char.obtain_method)) return false;
                }

                // 5. 募集タグ (AND検索: 選択したタグを「すべて」持っているか)
                // ※募集タグは通常、組み合わせて絞り込むためANDが一般的
                if (activeFilters.tags.length > 0) {
                    // char.tagsがない、またはリストでない場合は除外
                    if (!char.tags || !Array.isArray(char.tags)) return false;
                    
                    // 選択された全てのタグが含まれているかチェック
                    const hasAllTags = activeFilters.tags.every(selectedTag => 
                        char.tags.includes(selectedTag)
                    );
                    if (!hasAllTags) return false;
                }

                return true;
            });

            renderCharacters(filtered);
        }

        // キャラクター描画処理
        function renderCharacters(chars) {
            const container = document.getElementById('results');
            const countLabel = document.getElementById('result-count');
            
            container.innerHTML = '';
            countLabel.textContent = `検索結果: ${chars.length} 件`;

            if (chars.length === 0) {
                container.innerHTML = '<p style="grid-column: 1/-1; text-align:center;">該当するキャラクターが見つかりません。</p>';
                return;
            }

            chars.forEach(char => {
                // 画像がない場合のフォールバック（wikiruの仕様に依存）
                // URLが相対パスかもしれないので、base URLを考慮する必要があるかもしれません。
                // ここでは提供されたデータのままimg srcに入れます。
                // wikiruの画像は遅延ロード用の属性を持っていることが多いですが、
                // 今回抽出したJSON構造に合わせて調整してください。
                
                // HTML生成
                const card = document.createElement('a');
                card.href = char.url || '#';
                card.target = "_blank";
                card.className = 'char-card';

                // タグのHTML生成
                let tagsHtml = '';
                if(char.tags && Array.isArray(char.tags)){
                    tagsHtml = char.tags.map(t => `<span class="tag-badge">${t}</span>`).join('');
                }

                // 数値データの整形 (+値がある場合は表示するなど)
 const atk = char。attack？（char。attack。base +（char。attack。trust_bonus？`<span style='color:#29b6f6'>${char。attack。trust_bonus}</span>` : '')) : '-';
 </スクリプト>
 // 画像URLの処理（タタタタタちゃんの結果がううううう
 // JSON は、URL からは、能を付し〦、また、〜。
 // 前回のPythonURLURL（td一本URL）たかい
 // じょんちゃん、じょんちゃん、JSONのURL、じょんちゃん、じょんちゃん。
 // そりんがけんん
 const imgDisplay = `<div style="width:100%; height:100px; display:flex; align-items:center; justify-content:center; background:#333; font-size:2rem; font-weight:bold; color:#555;">${char。名前。charAt(0)}</div>`;

 ・・・・・。innerhtml = `
 <div class="char-img-box">
 ${imgDisplay}
 </分割>
 <div class="char-info">
 <div class="char-name">${char。割割}</div>
 <div class="char-tags">${char。職業} - ${char。ダンダン}</div>
 <div class="char-tags">${tagsHtml}</div>
 <div class="char-stats">
 <div class="stat-row"><span>HP:</span> ${char。hp？.base || '-'}</div>
 <div class="stat-row"><span>攻行:</span> ${atk}</div>
 <div class="stat-row"><span>防得:</span> ${char。防衛？.サ || '-'}</div>
 <div class="stat-row"><span>スパン:</span> ${char。ココ}</div>
 <div class="stat-row"><span>スパン:</span> ${char。block_count}</div>
 <div class="stat-row"><span>再配置:</span> ${char。redeploy_time？.split(' ')[0] || '-'}</div>
 </分割>
 </分割>
 `;
 むツツ。appendChild（あん）;
 });
        }

 // ・・・・・
 這数 resetFilters() {
 // ゆうえなえなえ
 むつむつむつ = {
 職業: [],
 サササ: [],
 ガ_ガ: [],
 取得_方法: [],
 サ: []
 };

 // そりんけんんん
 文書。querySelectorAll('.btn-セク。アクティブ')。forEach（btn => {
 btn。クスオスト。削除('削除き');
 });

 // 再描画
 renderCharacters（あくびのキャラクター）;
        }
 </やれやれ>
</体>
</html>
