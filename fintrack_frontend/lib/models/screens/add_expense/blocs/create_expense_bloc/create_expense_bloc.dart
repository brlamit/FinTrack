import 'package:bloc/bloc.dart';
import 'package:expense_repository/expense_repository.dart';

class CreateExpenseBloc extends Cubit<int> {
  final ExpenseRepository repo;
  CreateExpenseBloc(this.repo) : super(0);
}
