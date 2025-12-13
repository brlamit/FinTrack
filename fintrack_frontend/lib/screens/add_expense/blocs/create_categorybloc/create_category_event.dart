part of 'create_category_bloc.dart';

abstract class CreateCategoryEvent extends Equatable {
  const CreateCategoryEvent();

  @override
  List<Object?> get props => [];
}

class CreateCategory extends CreateCategoryEvent {
  final Category category;

  const CreateCategory(this.category);

  @override
  List<Object?> get props => [category];
}
