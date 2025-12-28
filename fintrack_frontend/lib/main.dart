import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:fintrack_frontend/services/api_service.dart';
import 'app.dart';
// import 'package:fintrack_frontend/services/in_memory_expense_repo.dart';
// import 'package:flutter_bloc/flutter_bloc.dart';
// import 'screens/home/blocs/get_expenses_bloc/get_expenses_bloc.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Firebase initialization removed for now.
  // await Firebase.initializeApp();
  // Supabase initialization

  await Supabase.initialize(
    url: 'https://rbvuivngveilamxliumb.supabase.co',
    anonKey:
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJidnVpdm5ndmVpbGFteGxpdW1iIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjI0NDAzMDQsImV4cCI6MjA3ODAxNjMwNH0.mJRJfJF2xavEz88DAIGrpQV92ya21k0CuJeSqR5dB7A',
  );

  // --- Debug: override backend host for testing on physical devices ---
  // If you're running the backend on your dev machine and testing on a
  // physical device, set ApiService.debugBackendOverride to
  // 'http://<your-pc-lan-ip>:8000/api' (example: http://192.168.1.42:8000/api)
  // Uncomment and set below when needed.
  // ApiService.debugBackendOverride = 'http://192.168.1.42:8000/api';

  // Initialize ApiService (loads stored token/user)
  await ApiService.init();

  runApp(const MyApp());
}

// MyApp widget comes from app.dart, which wraps the
// whole app in MyAppView (with global theming, routes, etc.).
