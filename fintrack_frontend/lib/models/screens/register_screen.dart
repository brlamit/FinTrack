import 'package:flutter/material.dart';
import '/services/api_service.dart';

class RegisterScreen extends StatefulWidget {
  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();

  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _usernameCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();

  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _usernameCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_passwordCtrl.text != _confirmCtrl.text) {
      setState(() => _error = "Passwords do not match");
      return;
    }

    setState(() {
      _error = null;
      _loading = true;
    });

    try {
      final success = await ApiService.register({
        "name": _nameCtrl.text.trim(),
        "email": _emailCtrl.text.trim(),
        "username": _usernameCtrl.text.trim(),
        "password": _passwordCtrl.text,
      });

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Registered successfully — please login")),
        );
        Navigator.pushReplacementNamed(context, "/login");
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Colors.blue.shade700,
              Colors.blue.shade400,
            ],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: EdgeInsets.symmetric(horizontal: 20),
            child: Card(
              elevation: 12,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(18),
              ),
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.person_add_alt_1,
                          size: 60, color: Colors.blue.shade700),
                      SizedBox(height: 8),
                      Text(
                        "Create Account",
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.blue.shade700,
                        ),
                      ),
                      SizedBox(height: 20),

                      _buildField("Full Name", _nameCtrl),
                      _buildField("Email", _emailCtrl,
                          keyboard: TextInputType.emailAddress),
                      _buildField("Username", _usernameCtrl),
                      _buildField("Password", _passwordCtrl, obscure: true),
                      _buildField("Confirm Password", _confirmCtrl,
                          obscure: true),

                      SizedBox(height: 12),

                      if (_error != null)
                        Text(
                          _error!,
                          style: TextStyle(
                              color: Colors.red, fontSize: 14, height: 1.3),
                        ),

                      SizedBox(height: 16),

                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _loading ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.blue.shade600,
                            padding: EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _loading
                              ? SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  "Register",
                                  style: TextStyle(fontSize: 16),
                                ),
                        ),
                      ),
                      SizedBox(height: 10),

                      TextButton(
                        onPressed: () =>
                            Navigator.pushReplacementNamed(context, "/login"),
                        child: Text(
                          "Already have an account? Log in",
                          style: TextStyle(color: Colors.blue.shade700),
                        ),
                      )
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildField(
    String label,
    TextEditingController controller, {
    bool obscure = false,
    TextInputType keyboard = TextInputType.text,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14.0),
      child: TextFormField(
        controller: controller,
        obscureText: obscure,
        keyboardType: keyboard,
        decoration: InputDecoration(
          labelText: label,
          labelStyle: TextStyle(color: Colors.blue.shade700),
          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: Colors.blue.shade600, width: 2),
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        validator: (value) =>
            value == null || value.isEmpty ? "Enter $label" : null,
      ),
    );
  }
}