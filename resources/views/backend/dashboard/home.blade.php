@extends('layouts.app')

@section('content')
<div class="content-wrapper flex-grow-1">
    <div class="container-fluid">
        <h2>Dynamic Data Visualization</h2>
        <form id="dataSelectionForm">
            <div class="form-group">
                <label for="dataType">Select Data Type:</label>
                <select id="dataType" class="form-control" onchange="fetchColumns()">
                    <option value="inventories">Inventory</option>
                    <option value="sales">Sales</option>
                    <option value="clock_in_out">Clock In/Out</option>
                </select>
            </div>
            <div class="form-group">
                <label for="graphType">Select Graph Type:</label>
                <select id="graphType" class="form-control">
                    <option value="bar">Bar</option>
                    <option value="line">Line</option>
                    <option value="pie">Pie</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="fetchAndRenderData()">Generate Graph</button>
            <button type="button" class="btn btn-secondary" onclick="downloadChart()">Download as PNG</button>
            <button type="button" class="btn btn-secondary" onclick="printClockReport()">Print Clock Report</button>
        </form>

        <canvas id="chartCanvas" width="400" height="200"></canvas>

        <form id="reportForm">
            <h3>Select Columns for Report</h3>
            <div id="columnCheckboxes"></div>

            <h3>Select Date Range</h3>
            <input type="date" name="start_date" required>
            <input type="date" name="end_date" required>

            <button type="button" onclick="generateReport()">Generate Report</button>
            <button type="button" onclick="showPreviewModal()">Preview PDF</button>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">PDF Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe id="pdfPreview" style="width:100%; height:500px;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="generateReport()">Generate PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Ensure jQuery is loaded first -->
<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        fetchColumns();
    });

    let chartInstance = null;

    // Set up the CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function fetchColumns() {
        const tableName = document.getElementById('dataType').value;
        fetch(`/api/columns?table=${tableName}`)
            .then(response => response.json())
            .then(columns => {
                const checkboxesDiv = document.getElementById('columnCheckboxes');
                checkboxesDiv.innerHTML = ''; // Clear existing checkboxes
                columns.forEach(column => {
                    const label = document.createElement('label');
                    label.innerHTML = `<input type="checkbox" name="columns[]" value="${column}"> ${column}`;
                    checkboxesDiv.appendChild(label);
                });
            });
    }

    function fetchAndRenderData() {
        const dataType = document.getElementById('dataType').value;
        const graphType = document.getElementById('graphType').value;
        let url = '';

        switch (dataType) {
            case 'inventories':
                url = '/api/inventory-data';
                break;
            case 'sales':
                url = '/api/sales-data';
                break;
            case 'clock_in_out':
                url = '/api/clock-in-out-data';
                break;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                renderChart(data, graphType);
            });
    }

    function renderChart(data, graphType) {
        const ctx = document.getElementById('chartCanvas').getContext('2d');

        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
            type: graphType,
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Data',
                    data: data.map(item => item.total_quantity || item.total_sales),
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function generateClockReportPDF(data) {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();

        const columns = ["Employee ID", "Clock In", "Clock Out"];
        const rows = data.map(entry => [entry.employee_id, entry.clock_in, entry.clock_out]);

        pdf.autoTable({
            head: [columns],
            body: rows,
        });

        pdf.save('clock_report.pdf');
    }

    function downloadChart() {
        const link = document.createElement('a');
        link.href = chartInstance.toBase64Image();
        link.download = 'chart.png';
        link.click();
    }

    function showPreviewModal() {
        const form = document.getElementById('reportForm');
        const formData = new FormData(form);
        const columns = Array.from(formData.getAll('columns[]'));

        // Fetch category names if category_id is selected
        if (columns.includes('category_id')) {
            fetch('/api/get-category-names')
                .then(response => response.json())
                .then(categoryNames => {
                    const index = columns.indexOf('category_id');
                    if (index !== -1) {
                        columns[index] = 'Category Name';
                    }
                    generatePDFPreview(columns, categoryNames);
                });
        } else {
            generatePDFPreview(columns, []);
        }
    }

    function generatePDFPreview(columns, categoryNames) {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        pdf.text("PDF Preview", 10, 10);
        pdf.autoTable({
            head: [columns],
            body: [], // Add sample data if needed
        });

        // Show PDF in modal
        const pdfDataUri = pdf.output('datauristring');
        document.getElementById('pdfPreview').src = pdfDataUri;
        $('#previewModal').modal('show');
    }

    function generateReport() {
        const form = document.getElementById('reportForm');
        const formData = new FormData(form);

        fetch('/api/generate-report', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'report.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
        });
    }
</script>
@endsection