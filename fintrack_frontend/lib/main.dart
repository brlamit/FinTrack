import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
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

  await dotenv.load(fileName: ".env");

  // Initialize Supabase BEFORE runApp()
  await Supabase.initialize(
    url: dotenv.env['SUPABASE_URL']!,
    anonKey: dotenv.env['SUPABASE_ANON_KEY']!,
  );

  // Initialize Hive
  await Hive.initFlutter();
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
      home: HomeScreen(),

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
