function updateNowTime(dateId = 'now-date', timeId = 'now-time') {
    const now = new Date();
    const week = ['日', '月', '火', '水', '木', '金', '土'];
    const y = now.getFullYear();
    const m = now.getMonth() + 1;
    const d = now.getDate();
    const w = week[now.getDay()];
    const h = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');

    // 日付・時刻をセット
    const dateEl = document.getElementById(dateId);
    if (dateEl) {
        dateEl.textContent = `${y}年${m}月${d}日(${w})`;
    }
    const timeEl = document.getElementById(timeId);
    if (timeEl) {
        timeEl.textContent = `${h}:${min}`;
    }
}