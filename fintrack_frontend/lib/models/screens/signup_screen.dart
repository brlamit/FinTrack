import 'package:fintrack_frontend/models/screens/login_screen.dart';
import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class SignupScreen extends StatefulWidget {
  const SignupScreen({super.key});

  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  final name = TextEditingController();
  final email = TextEditingController();
  final password = TextEditingController();
  final username = TextEditingController();
  bool loading = false;

  Future<void> signup() async {
    setState(() => loading = true);

    try {
      final res =
          await Supabase.instance.client.auth.signUp(
        email: email.text.trim(),
        password: password.text.trim(),
        data: {
          'name': name.text,
          'username': username.text,
        },
      );

      if (res.user != null) {
        Navigator.push(
          // ignore: use_build_context_synchronously
          context,
          MaterialPageRoute(
            builder: (_) => LoginScreen(),
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text("Signup Failed: $e")),
      );
    }

    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Sign Up")),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            children: [
              TextField(controller: name, decoration: const InputDecoration(labelText: "Full Name")),
              const SizedBox(height: 10),
              TextField(controller: username, decoration: const InputDecoration(labelText: "Username")),
              const SizedBox(height: 10),
              TextField(controller: email, decoration: const InputDecoration(labelText: "Email")),
              const SizedBox(height: 10),
              TextField(controller: password, obscureText: true, decoration: const InputDecoration(labelText: "Password")),
              const SizedBox(height: 20),

              ElevatedButton(
                onPressed: loading ? null : signup,
                child: loading ? const CircularProgressIndicator() : const Text("Create Account"),
                
              ),
            ],
          ),
        ),
      ),
    );
  }
}
