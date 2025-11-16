import 'package:flutter/material.dart';
import 'package:fintrack_frontend/models/trancs.dart';
import 'package:fintrack_frontend/models/screens/expenses_home_screen.dart';
import 'package:hive_flutter/hive_flutter.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Hive
  await Hive.initFlutter();

  // Register the TransactionAdapter
  Hive.registerAdapter(TransactionAdapter());

  // Open the Hive box
  await Hive.openBox('transactions');

  runApp(const ExpenseTrackerApp());
}

class ExpenseTrackerApp extends StatelessWidget {
  const ExpenseTrackerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return const MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'GFG Expense Tracker',
      home: ExpenseHomeScreen(),
    );
  }
}