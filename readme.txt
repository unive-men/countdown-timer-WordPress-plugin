=== countdown-timer-WordPress-plugin ===
必要なWordPressバージョン: 5.3以上
安定版タグ: 1.0.0
ライセンス: GPLv2 またはそれ以降
ライセンスURI: https://www.gnu.org/licenses/gpl-2.0.html　

進捗メーター付きのカウントダウンタイマーをショートコードで追加します。Adds a shortcode countdown timer with a progress meter. 

== 使用方法 ==

基本:

[countdown_timer start="2026-08-01 00:00" target="2026-08-31 18:00"]

すべての属性を使用する場合:

[countdown_timer start="2026-08-01 00:00" target="2026-08-31 18:00" label="remaining" red_under="3" end_text="終了"]

属性:

* start: 必須。メーターの開始日時。WordPressのタイムゾーン設定に基づいて解釈されます。
* target: 必須。終了日時。WordPressのタイムゾーン設定に基づいて解釈されます。
* label: オプション。"remaining" は「残り○○日」と表示し、"until" は「あと○○日」と表示します。デフォルトは"remaining"です。
* red_under: オプション。値を設定すると、残り日数がその値以下になった際に日数の数字が赤色になります。
* end_text: オプション。終了日時を過ぎた後に表示されるテキスト。デフォルトでは"終了"と表示されます。

== 注意事項 ==

メーターは開始時に0%、終了時に100%となります。開始前は、メーターは0%のままです。終了後は、ラベルが終了時のテキストに変わり、メーターは100%のままとなります。
CSSおよびJavaScriptファイルは意図的に軽量化されており、ショートコードのスタイルを確実に適用するためにフロントエンドのページで読み込まれます。
