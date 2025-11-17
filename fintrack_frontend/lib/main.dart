import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

// Screens
import 'package:fintrack_frontend/models/screens/home_screen.dart';
import 'package:fintrack_frontend/models/screens/login_screen.dart';
import 'package:fintrack_frontend/models/screens/register_screen.dart';
import 'package:fintrack_frontend/models/screens/expenses_home_screen.dart';
import 'package:fintrack_frontend/models/screens/add_tranc.dart';
import 'package:fintrack_frontend/models/screens/trancs_screen.dart';
import 'package:fintrack_frontend/models/screens/report.dart';

// Models (Hive)
import 'package:fintrack_frontend/models/trancs.dart';   // Transaction Model 

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

    // Initialize Supabase BEFORE runApp()
  await Supabase.initialize(
    url: 'https://xxwxtznerssubuohctsk.supabase.co',
    anonKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Inh4d3h0em5lcnNzdWJ1b2hjdHNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMTU2OTcsImV4cCI6MjA3Nzg5MTY5N30.AghKN85ICAQmDqXOgrR8LrhmFVj2ds3b4mqf7nEuUS4',
  );

  // Initialize Hive
  await Hive.initFlutter();

  // Register the TransactionAdapter
  Hive.registerAdapter(TransactionAdapter());

  // Open the Hive box
  await Hive.openBox<Transaction>('transactions');

  runApp(const ExpenseTrackerApp());
}

class ExpenseTrackerApp extends StatelessWidget {
  const ExpenseTrackerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'FinTrack App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.indigo,
      ),

      // Initial screen
      home: LoginScreen(),

      // App routes
      routes: {
        '/login': (context) =>  LoginScreen(),
        '/register': (context) => RegisterScreen(),
        '/home': (context) =>  HomeScreen(),
        '/expenses_home': (context) =>  ExpenseHomeScreen(),
        '/add_transaction': (context) => AddTransactionScreen(),
        '/transactions': (context) => TransactionScreen(),
        '/report': (context) =>  ReportsScreen(),
      },
    );
  }
}
