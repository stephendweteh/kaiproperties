import 'package:flutter/material.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';
import 'package:path_provider/path_provider.dart';
import 'dart:io';
import '../models/cost_analysis_model.dart';
import '../services/api_service.dart';

class CostAnalysisProvider extends ChangeNotifier {
  final ApiService _apiService;

  CostAnalysisData? _data;
  bool _loading = false;
  bool _exporting = false;
  String? _error;

  CostAnalysisProvider(this._apiService);

  CostAnalysisData? get data => _data;
  bool get loading => _loading;
  bool get exporting => _exporting;
  String? get error => _error;

  Future<void> load() async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/cost-analysis');
      final json = response['data'] as Map<String, dynamic>?;
      
      if (json != null) {
        _data = CostAnalysisData.fromJson(json);
      }
    } catch (e) {
      _error = 'Failed to load cost analysis data: ${e.toString()}';
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> refresh() => load();

  Future<void> exportToPDF() async {
    if (_data == null) {
      _error = 'No data available to export';
      notifyListeners();
      return;
    }

    _exporting = true;
    notifyListeners();

    try {
      final pdf = pw.Document();
      
      pdf.addPage(
        pw.Page(
          build: (pw.Context context) {
            return pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text(
                  'Cost Analysis Report',
                  style: pw.TextStyle(fontSize: 24, fontWeight: pw.FontWeight.bold),
                ),
                pw.SizedBox(height: 10),
                pw.Text(
                  'Generated on: ${DateTime.now().toString().split('.')[0]}',
                  style: const pw.TextStyle(fontSize: 10),
                ),
                pw.Divider(),
                pw.SizedBox(height: 20),
                _buildPdfMetrics(),
                pw.SizedBox(height: 20),
                pw.Text(
                  'Cost Breakdown',
                  style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
                ),
                pw.SizedBox(height: 10),
                _buildPdfBreakdown(),
                pw.SizedBox(height: 20),
                pw.Text(
                  'Project Summary',
                  style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
                ),
                pw.SizedBox(height: 10),
                _buildPdfProjectSummary(),
              ],
            );
          },
        ),
      );

      final output = await getApplicationDocumentsDirectory();
      final file = File('${output.path}/cost_analysis_${DateTime.now().millisecondsSinceEpoch}.pdf');
      await file.writeAsBytes(await pdf.save());

      await Share.shareXFiles(
        [XFile(file.path)],
        subject: 'Cost Analysis Report',
      );

      _exporting = false;
      notifyListeners();
    } catch (e) {
      _error = 'Failed to export PDF: ${e.toString()}';
      _exporting = false;
      notifyListeners();
    }
  }

  Future<void> shareAsText() async {
    if (_data == null) {
      _error = 'No data available to share';
      notifyListeners();
      return;
    }

    try {
      final text = _generateTextReport();
      await Share.share(
        text,
        subject: 'Cost Analysis Report',
      );
    } catch (e) {
      _error = 'Failed to share report: ${e.toString()}';
      notifyListeners();
    }
  }

  String _generateTextReport() {
    final data = _data!;
    final buffer = StringBuffer();

    buffer.writeln('COST ANALYSIS REPORT');
    buffer.writeln('Generated on: ${DateTime.now().toString().split('.')[0]}');
    buffer.writeln('\n' + '='*50 + '\n');

    buffer.writeln('KEY METRICS');
    buffer.writeln('Total Budget: GHS ${_formatCurrency(data.totalBudget)}');
    buffer.writeln('Total Cost: GHS ${_formatCurrency(data.totalCost)}');
    buffer.writeln('Cost Variance: GHS ${_formatCurrency(data.totalBudget - data.totalCost)}');
    buffer.writeln('Budget Utilization: ${((data.totalCost / data.totalBudget) * 100).toStringAsFixed(2)}%');
    buffer.writeln('\n' + '-'*50 + '\n');

    if (data.costBreakdown.isNotEmpty) {
      buffer.writeln('COST BREAKDOWN');
      for (final item in data.costBreakdown) {
        buffer.writeln('${item.category}: GHS ${_formatCurrency(item.amount)} (${item.percentage.toStringAsFixed(2)}%)');
      }
      buffer.writeln('\n' + '-'*50 + '\n');
    }

    if (data.projectSummary.isNotEmpty) {
      buffer.writeln('PROJECT SUMMARY');
      for (final project in data.projectSummary) {
        buffer.writeln('${project.projectName}');
        buffer.writeln('  Budget: GHS ${_formatCurrency(project.budget)}');
        buffer.writeln('  Actual Cost: GHS ${_formatCurrency(project.actualCost)}');
        buffer.writeln('  Variance: GHS ${_formatCurrency(project.budget - project.actualCost)}');
        buffer.writeln('  Utilization: ${project.utilization.toStringAsFixed(2)}%');
        buffer.writeln('  Status: ${project.status}');
        buffer.writeln('');
      }
    }

    return buffer.toString();
  }

  pw.Widget _buildPdfMetrics() {
    final data = _data!;
    return pw.Column(
      children: [
        pw.Text(
          'Key Metrics',
          style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold),
        ),
        pw.SizedBox(height: 10),
        pw.Row(
          mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
          children: [
            pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text('Total Budget'),
                pw.Text('GHS ${_formatCurrency(data.totalBudget)}', style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
              ],
            ),
            pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text('Total Cost'),
                pw.Text('GHS ${_formatCurrency(data.totalCost)}', style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
              ],
            ),
            pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text('Variance'),
                pw.Text('GHS ${_formatCurrency(data.totalBudget - data.totalCost)}', style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
              ],
            ),
            pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text('Utilization'),
                pw.Text('${((data.totalCost / data.totalBudget) * 100).toStringAsFixed(2)}%', style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
              ],
            ),
          ],
        ),
      ],
    );
  }

  pw.Widget _buildPdfBreakdown() {
    final data = _data!;
    if (data.costBreakdown.isEmpty) return pw.SizedBox();

    return pw.Column(
      children: data.costBreakdown
          .map((item) => pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text(item.category),
                  pw.Text('${item.percentage.toStringAsFixed(2)}%'),
                  pw.Text('GHS ${_formatCurrency(item.amount)}'),
                ],
              ))
          .toList(),
    );
  }

  pw.Widget _buildPdfProjectSummary() {
    final data = _data!;
    if (data.projectSummary.isEmpty) return pw.SizedBox();

    return pw.Column(
      children: data.projectSummary
          .map((project) => pw.Padding(
                padding: const pw.EdgeInsets.only(bottom: 10),
                child: pw.Column(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    pw.Text(project.projectName, style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
                    pw.Text('Budget: GHS ${_formatCurrency(project.budget)}'),
                    pw.Text('Actual: GHS ${_formatCurrency(project.actualCost)}'),
                    pw.Text('Status: ${project.status}'),
                  ],
                ),
              ))
          .toList(),
    );
  }

  String _formatCurrency(double amount) {
    return amount.toStringAsFixed(2).replaceAllMapped(
        RegExp(r'\B(?=(\d{3})+(?!\d))'), (match) => ',');
  }
}
