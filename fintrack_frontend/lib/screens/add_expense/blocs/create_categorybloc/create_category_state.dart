part of 'create_category_bloc.dart';

abstract class CreateCategoryState extends Equatable {
  const CreateCategoryState();

  @override
  List<Object?> get props => [];
}

class CreateCategoryInitial extends CreateCategoryState {}

class CreateCategoryLoading extends CreateCategoryState {}

class CreateCategorySuccess extends CreateCategoryState {}

class CreateCategoryFailure extends CreateCategoryState {
  final String error;

  const CreateCategoryFailure({this.error = ''});

  @override
  List<Object?> get props => [error];
}
