import 'dart:math';
import 'package:flutter/material.dart';
import 'package:syncfusion_flutter_charts/charts.dart';
import 'package:redacted/redacted.dart';

class MyChart extends StatefulWidget {
  const MyChart({super.key});

  @override
  State<MyChart> createState() => _MyChartState();
}

class _MyChartState extends State<MyChart> {
  late List<ChartData> barData;
  late List<LineData> lineData;
  late List<PieData> pieData;
  late List<HistogramData> histoData;

  @override
  void initState() {
    super.initState();

    barData = [
      ChartData(0, 2),
      ChartData(1, 3),
      ChartData(2, 2),
      ChartData(3, 4.5),
      ChartData(4, 3.8),
      ChartData(5, 1.5),
      ChartData(6, 4),
      ChartData(7, 3.8),
    ];

    lineData = [
      LineData(0, 1),
      LineData(1, 2),
      LineData(2, 2.5),
      LineData(3, 3),
      LineData(4, 3.8),
      LineData(5, 4.5),
      LineData(6, 3.9),
      LineData(7, 4.2),
    ];

    pieData = [
      PieData("Food", 25),
      PieData("Transport", 18),
      PieData("Rent", 35),
      PieData("Entertainment", 10),
      PieData("Others", 12),
    ];

    histoData = List.generate(
      50,
      (index) => HistogramData(Random().nextInt(100).toDouble()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        children: [
          const SizedBox(height: 20),

          // -------------------------------------------------------------
          // BAR CHART
          // -------------------------------------------------------------
          Text("Bar Graph", style: Theme.of(context).textTheme.titleLarge),
          SizedBox(
            height: 300,
            child: SfCartesianChart(
              plotAreaBorderWidth: 0,
              primaryXAxis: CategoryAxis(),
              primaryYAxis: NumericAxis(minimum: 0, maximum: 5),
              series: <CartesianSeries>[
                ColumnSeries<ChartData, String>(
                  dataSource: barData,
                  xValueMapper: (d, _) => d.x.toString(),
                  yValueMapper: (d, _) => d.y,
                  onCreateShader: (details) {
                    return LinearGradient(
                      colors: [
                        Theme.of(context).colorScheme.primary,
                        Theme.of(context).colorScheme.secondary,
                        Theme.of(context).colorScheme.tertiary,
                      ],
                    ).createShader(details.rect);
                  },
                  borderRadius: const BorderRadius.all(Radius.circular(6)),
                )
              ],
            ),
          ).redacted(context: context, redact: true),

          const SizedBox(height: 40),

          // -------------------------------------------------------------
          // PIE CHART
          // -------------------------------------------------------------
          Text("Pie Chart", style: Theme.of(context).textTheme.titleLarge),
          SizedBox(
            height: 300,
            child: SfCircularChart(
              legend: const Legend(isVisible: true),
              series: <CircularSeries>[
                PieSeries<PieData, String>(
                  dataSource: pieData,
                  xValueMapper: (d, _) => d.label,
                  yValueMapper: (d, _) => d.value,
                  dataLabelSettings: const DataLabelSettings(isVisible: true),
                )
              ],
            ),
          ).redacted(context: context, redact: true),

          const SizedBox(height: 40),

          // -------------------------------------------------------------
          // LINE CHART
          // -------------------------------------------------------------
          Text("Line Graph", style: Theme.of(context).textTheme.titleLarge),
          SizedBox(
            height: 300,
            child: SfCartesianChart(
              primaryXAxis: CategoryAxis(),
              primaryYAxis: NumericAxis(),
              series: <CartesianSeries>[
                LineSeries<LineData, String>(
                  dataSource: lineData,
                  xValueMapper: (d, _) => d.x.toString(),
                  yValueMapper: (d, _) => d.y,
                  markerSettings: const MarkerSettings(isVisible: true),
                ),
              ],
            ),
          ).redacted(context: context, redact: true),

          const SizedBox(height: 40),

          // -------------------------------------------------------------
          // HISTOGRAM
          // -------------------------------------------------------------
          Text("Histogram", style: Theme.of(context).textTheme.titleLarge),
          SizedBox(
            height: 300,
            child: SfCartesianChart(
              primaryXAxis: NumericAxis(),
              primaryYAxis: NumericAxis(),
              series: <CartesianSeries>[
                HistogramSeries<HistogramData, double>(
                  dataSource: histoData,
                  yValueMapper: (d, _) => d.value,
                  binInterval: 10,
                ),
              ],
            ),
          ).redacted(context: context, redact: true),

          const SizedBox(height: 50),
        ],
      ),
    );
  }
}

// ----------------------------------------------------------------------
// DATA MODELS
// ----------------------------------------------------------------------

class ChartData {
  final int x;
  final double y;
  ChartData(this.x, this.y);
}

class LineData {
  final int x;
  final double y;
  LineData(this.x, this.y);
}

class PieData {
  final String label;
  final double value;
  PieData(this.label, this.value);
}

class HistogramData {
  final double value;
  HistogramData(this.value);
}
