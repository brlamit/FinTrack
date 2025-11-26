import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:fintrack_frontend/models/screens/login_screen.dart';
import 'package:fintrack_frontend/models/screens/signup_screen.dart';
import 'package:fintrack_frontend/models/screens/home_screen.dart';
import 'package:fintrack_frontend/models/screens/forgot_password_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Supabase.initialize(
    url: 'https://rbvuivngveilamxliumb.supabase.co',
    anonKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJidnVpdm5ndmVpbGFteGxpdW1iIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjI0NDAzMDQsImV4cCI6MjA3ODAxNjMwNH0.mJRJfJF2xavEz88DAIGrpQV92ya21k0CuJeSqR5dB7A',
  );

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,

      initialRoute: '/login',

      routes: {
        '/login': (_) => const LoginScreen(),
        '/register': (_) => const SignupScreen(),
        '/home': (_) => const HomeScreen(),
        '/forgot-password': (_) => const ForgotPasswordScreen(),
      },
    );
  }
}
