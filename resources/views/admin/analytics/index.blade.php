@extends('layouts.admin')

@section('title', 'Visitas & Currículos')
@section('topbar', 'Indicadores de recrutamento')

@section('content')
<div class="page-head analytics-page-head">
    <div>
        <span class="eyebrow">Desempenho do site</span>
        <h1>Visitas & Currículos</h1>
        <p>Acompanhe o movimento do site público e os currículos enviados em cada mês.</p>
    </div>

    <form class="analytics-month-form" method="GET" action="{{ route('admin.analytics.index') }}">
        <a class="btn btn-light" href="{{ route('admin.analytics.index', ['month' => $previousMonthValue]) }}" aria-label="Mês anterior">←</a>
        <label class="month-picker">
            <span>Mês analisado</span>
            <input type="month" name="month" value="{{ $selectedMonthValue }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()">
        </label>
        @if($canGoNext)
            <a class="btn btn-light" href="{{ route('admin.analytics.index', ['month' => $nextMonthValue]) }}" aria-label="Próximo mês">→</a>
        @else
            <button class="btn btn-light" type="button" disabled aria-label="Próximo mês indisponível">→</button>
        @endif
    </form>
</div>

<div class="analytics-period-banner">
    <div>
        <span>Período selecionado</span>
        <strong>{{ ucfirst($selectedMonthLabel) }}</strong>
    </div>
    <p>Os dados de visitação começam a ser contabilizados depois que esta versão for instalada. Candidaturas antigas continuam aparecendo normalmente.</p>
</div>

<section class="analytics-stats">
    <article class="analytics-stat">
        <span>Visitantes únicos estimados</span>
        <strong>{{ number_format($uniqueVisitors, 0, ',', '.') }}</strong>
        <small>
            @if($visitorDelta === null)
                Primeiro mês com comparação disponível
            @elseif($visitorDelta > 0)
                ↑ {{ number_format($visitorDelta, 1, ',', '.') }}% vs. mês anterior
            @elseif($visitorDelta < 0)
                ↓ {{ number_format(abs($visitorDelta), 1, ',', '.') }}% vs. mês anterior
            @else
                Sem alteração vs. mês anterior
            @endif
        </small>
    </article>

    <article class="analytics-stat">
        <span>Visualizações de páginas</span>
        <strong>{{ number_format($pageViews, 0, ',', '.') }}</strong>
        <small>Total de páginas públicas abertas no período</small>
    </article>

    <article class="analytics-stat analytics-stat-brand">
        <span>Currículos enviados</span>
        <strong>{{ number_format($resumesSubmitted, 0, ',', '.') }}</strong>
        <small>
            @if($resumeDelta === null)
                Primeiro mês com comparação disponível
            @elseif($resumeDelta > 0)
                ↑ {{ number_format($resumeDelta, 1, ',', '.') }}% vs. mês anterior
            @elseif($resumeDelta < 0)
                ↓ {{ number_format(abs($resumeDelta), 1, ',', '.') }}% vs. mês anterior
            @else
                Sem alteração vs. mês anterior
            @endif
        </small>
    </article>

    <article class="analytics-stat">
        <span>Conversão visitante → currículo</span>
        <strong>{{ number_format($conversionRate, 1, ',', '.') }}%</strong>
        <small>Currículos enviados ÷ visitantes únicos estimados</small>
    </article>
</section>

<div class="analytics-chart-grid">
    <section class="panel analytics-chart-panel">
        <div class="panel-head">
            <div>
                <h2>Visitantes ao longo do mês</h2>
                <p class="subtle">Sessões únicas estimadas por dia</p>
            </div>
            <span class="analytics-legend"><i class="legend-visitors"></i> Visitantes</span>
        </div>
        <div class="analytics-canvas-wrap">
            <canvas id="visitorsChart" role="img" aria-label="Gráfico de visitantes únicos por dia"></canvas>
            <div class="chart-empty" id="visitorsEmpty" hidden>Nenhuma visita registrada neste mês.</div>
        </div>
    </section>

    <section class="panel analytics-chart-panel">
        <div class="panel-head">
            <div>
                <h2>Currículos enviados</h2>
                <p class="subtle">Candidaturas recebidas por dia</p>
            </div>
            <span class="analytics-legend"><i class="legend-resumes"></i> Currículos</span>
        </div>
        <div class="analytics-canvas-wrap">
            <canvas id="resumesChart" role="img" aria-label="Gráfico de currículos enviados por dia"></canvas>
            <div class="chart-empty" id="resumesEmpty" hidden>Nenhum currículo enviado neste mês.</div>
        </div>
    </section>
</div>

<section class="panel analytics-chart-panel analytics-wide-chart">
    <div class="panel-head">
        <div>
            <h2>Comparativo dos últimos 12 meses</h2>
            <p class="subtle">Visitantes únicos estimados e currículos enviados por mês</p>
        </div>
        <div class="analytics-legends">
            <span class="analytics-legend"><i class="legend-visitors"></i> Visitantes</span>
            <span class="analytics-legend"><i class="legend-resumes"></i> Currículos</span>
        </div>
    </div>
    <div class="analytics-canvas-wrap analytics-canvas-wide">
        <canvas id="monthlyChart" role="img" aria-label="Comparativo mensal entre visitantes e currículos enviados"></canvas>
    </div>
</section>

<section class="analytics-note">
    <strong>Como a contagem funciona</strong>
    <p>“Visitantes únicos” é uma estimativa baseada em sessões do próprio Laravel. O sistema não salva endereço IP para esse relatório. Recarregar várias páginas aumenta “visualizações”, mas não aumenta o visitante único enquanto a mesma sessão estiver ativa.</p>
</section>

<script>
(() => {
    const daily = @json($dailySeries);
    const monthly = @json($monthlySeries);
    const style = getComputedStyle(document.documentElement);
    const colors = {
        text: style.getPropertyValue('--text').trim() || '#0b0b0b',
        muted: style.getPropertyValue('--muted').trim() || '#6f6f6f',
        border: style.getPropertyValue('--border').trim() || '#dedede',
        brand: style.getPropertyValue('--brand').trim() || '#ed6098',
        ink: style.getPropertyValue('--ink').trim() || '#050505',
        paper: style.getPropertyValue('--paper-2').trim() || '#ffffff'
    };

    const fmt = new Intl.NumberFormat('pt-BR');

    function prepareCanvas(canvas, minHeight = 300) {
        const width = Math.max(canvas.parentElement.clientWidth, 320);
        const height = minHeight;
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        const ctx = canvas.getContext('2d');
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        return { ctx, width, height };
    }

    function niceMax(value) {
        if (value <= 5) return 5;
        const magnitude = Math.pow(10, Math.floor(Math.log10(value)));
        return Math.ceil(value / magnitude) * magnitude;
    }

    function drawAxes(ctx, width, height, maxValue, padding) {
        const plotW = width - padding.left - padding.right;
        const plotH = height - padding.top - padding.bottom;
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';

        for (let i = 0; i <= 4; i++) {
            const y = padding.top + (plotH * i / 4);
            const value = Math.round(maxValue * (1 - i / 4));
            ctx.strokeStyle = colors.border;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
            ctx.fillStyle = colors.muted;
            ctx.fillText(fmt.format(value), padding.left - 9, y);
        }

        return { plotW, plotH };
    }

    function drawLineChart(canvasId, emptyId, rows, key, strokeColor) {
        const canvas = document.getElementById(canvasId);
        const empty = document.getElementById(emptyId);
        const values = rows.map(row => Number(row[key]) || 0);
        const hasData = values.some(value => value > 0);
        empty.hidden = hasData;
        canvas.style.opacity = hasData ? '1' : '.28';

        const { ctx, width, height } = prepareCanvas(canvas, 300);
        ctx.clearRect(0, 0, width, height);

        const padding = { top: 22, right: 18, bottom: 42, left: 48 };
        const maxValue = niceMax(Math.max(...values, 1));
        const { plotW, plotH } = drawAxes(ctx, width, height, maxValue, padding);
        const step = rows.length > 1 ? plotW / (rows.length - 1) : plotW;

        ctx.strokeStyle = strokeColor;
        ctx.lineWidth = 3;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.beginPath();

        values.forEach((value, index) => {
            const x = padding.left + step * index;
            const y = padding.top + plotH - (value / maxValue) * plotH;
            if (index === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();

        values.forEach((value, index) => {
            const x = padding.left + step * index;
            const y = padding.top + plotH - (value / maxValue) * plotH;
            ctx.fillStyle = strokeColor;
            ctx.beginPath();
            ctx.arc(x, y, value > 0 ? 3.3 : 2, 0, Math.PI * 2);
            ctx.fill();
        });

        ctx.font = '10px Inter, sans-serif';
        ctx.fillStyle = colors.muted;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        const labelEvery = rows.length > 20 ? 5 : (rows.length > 12 ? 3 : 1);
        rows.forEach((row, index) => {
            if (index % labelEvery !== 0 && index !== rows.length - 1) return;
            const x = padding.left + step * index;
            ctx.fillText(row.label, x, height - padding.bottom + 13);
        });
    }

    function drawMonthlyBars() {
        const canvas = document.getElementById('monthlyChart');
        const { ctx, width, height } = prepareCanvas(canvas, 340);
        ctx.clearRect(0, 0, width, height);

        const padding = { top: 24, right: 18, bottom: 52, left: 50 };
        const maxValue = niceMax(Math.max(...monthly.flatMap(row => [Number(row.visitors) || 0, Number(row.resumes) || 0]), 1));
        const { plotW, plotH } = drawAxes(ctx, width, height, maxValue, padding);
        const groupW = plotW / Math.max(monthly.length, 1);
        const barW = Math.max(5, Math.min(18, groupW * .25));

        monthly.forEach((row, index) => {
            const center = padding.left + groupW * index + groupW / 2;
            const visitorH = (Number(row.visitors) / maxValue) * plotH;
            const resumeH = (Number(row.resumes) / maxValue) * plotH;

            ctx.fillStyle = colors.ink;
            ctx.fillRect(center - barW - 2, padding.top + plotH - visitorH, barW, visitorH);
            ctx.fillStyle = colors.brand;
            ctx.fillRect(center + 2, padding.top + plotH - resumeH, barW, resumeH);

            ctx.font = '10px Inter, sans-serif';
            ctx.fillStyle = colors.muted;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            ctx.fillText(row.label, center, height - padding.bottom + 15);
        });
    }

    function renderAll() {
        drawLineChart('visitorsChart', 'visitorsEmpty', daily, 'visitors', colors.ink);
        drawLineChart('resumesChart', 'resumesEmpty', daily, 'resumes', colors.brand);
        drawMonthlyBars();
    }

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(renderAll, 120);
    });

    renderAll();
})();
</script>
@endsection
